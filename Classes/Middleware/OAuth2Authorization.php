<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Middleware;

use FGTCLB\OAuth2Server\Configuration;
use FGTCLB\OAuth2Server\Domain\Entity\User;
use FGTCLB\OAuth2Server\Server\ServerFactory;
use FGTCLB\OAuth2Server\Session\UserSession;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Handler for OAuth2 authorization requests
 *
 * If a user is logged in, an authorization request is completed automatically,
 * otherwise the authorization request is stored in the frontend session and
 * restored once a user has logged in. Login is enforced by a redirect to the
 * login page with a request to redirect back to this middleware after login.
 *
 * @see https://oauth2.thephpleague.com/authorization-server/auth-code-grant/#part-one
 */
final class OAuth2Authorization implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected SiteFinder $siteFinder;
    protected Context $context;
    protected Configuration $configuration;

    public function __construct(Configuration $configuration, Context $context, SiteFinder $siteFinder)
    {
        $this->configuration = $configuration;
        $this->context = $context;
        $this->siteFinder = $siteFinder;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() !== '/oauth/authorize') {
            return $handler->handle($request);
        }

        $factory = new ServerFactory();
        $server = $factory->buildAuthorizationServer();

        $frontendUser = $request->getAttribute('frontend.user');

        if (!$frontendUser instanceof FrontendUserAuthentication) {
            $this->logger->warning('No frontend user logged in. Cannot continue with OAuth2 Authorization request.');
            return $handler->handle($request);
        }

        $userSession = new UserSession($frontendUser);
        $authorizationRequest = $userSession->getData('oauth2.authorizationRequest');

        $router = $this->siteFinder->getSiteByPageId($this->configuration->getLoginPage())->getRouter($this->context);

        if (!$authorizationRequest) {
            try {
                $authorizationRequest = $server->validateAuthorizationRequest($request);
            } catch (OAuthServerException $e) {
                $this->logger->warning(sprintf('Validating authorization request failed: %s', $e->getMessage()));

                $redirectUri = $router->generateUri($this->configuration->getLoginPage());

                return new RedirectResponse($redirectUri);
            }
        }

        if (!$this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn', false)) {
            $userSession->setData('oauth2.authorizationRequest', serialize($authorizationRequest));

            $redirectUri = $router->generateUri($this->configuration->getLoginPage(), ['redirect_url' => $request->getUri()->getPath()]);

            return new RedirectResponse($redirectUri);
        }

        // With TYPO3 11.5.17 it takes 3 loops to unserialize the AuthorizationRequest
        $count = 0;
        while (!$authorizationRequest instanceof AuthorizationRequest && $count < 10) {
            $authorizationRequest = unserialize($authorizationRequest, ['allowed_classes' => ['League\OAuth2\Server\RequestTypes\AuthorizationRequest', 'FGTCLB\OAuth2Server\Domain\Entity\Client']]);
            $count++;
        }

        // Handle error when $authorizationRequest is still a string
        if (!$authorizationRequest instanceof AuthorizationRequest) {
            $this->logger->error('Unserializing of AuthorizationRequest failed!');

            $redirectUri = $router->generateUri($this->configuration->getLoginPage());

            return new RedirectResponse($redirectUri);
        }

        $authorizationRequest->setUser(new User((string)$this->context->getPropertyFromAspect('frontend.user', 'id')));
        $authorizationRequest->setAuthorizationApproved(true);

        $userSession->removeData('oauth2.authorizationRequest');

        try {
            return $server->completeAuthorizationRequest($authorizationRequest, new Response());
        } catch (OAuthServerException $e) {
            return $e->generateHttpResponse(new Response());
        }
    }
}
