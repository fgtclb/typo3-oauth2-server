<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Middleware;

use FGTCLB\OAuth2Server\Configuration;
use FGTCLB\OAuth2Server\Domain\Entity\Client;
use FGTCLB\OAuth2Server\Domain\Entity\Scope;
use FGTCLB\OAuth2Server\Domain\Entity\User;
use FGTCLB\OAuth2Server\Exception\IdentifierEmptyException;
use FGTCLB\OAuth2Server\Server\ServerFactory;
use FGTCLB\OAuth2Server\Session\UserSession;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
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
final class OAuth2Authorization implements MiddlewareInterface
{
    public function __construct(
        private readonly Configuration $configuration,
        private readonly Context $context,
        private readonly SiteFinder $siteFinder,
        private readonly ServerFactory $serverFactory,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     * @throws IdentifierEmptyException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() !== $this->configuration->getAuthEndpoint()) {
            return $handler->handle($request);
        }

        $server = $this->serverFactory->buildAuthorizationServer();

        $frontendUser = $request->getAttribute('frontend.user');

        if (!$frontendUser instanceof FrontendUserAuthentication) {
            $this->logger->warning('No frontend user logged in. Cannot continue with OAuth2 Authorization request.');
            return $handler->handle($request);
        }

        $userSession = new UserSession($frontendUser);
        $authorizationRequest = $this->restoreAuthorizationRequest($userSession);

        $router = $this->siteFinder->getSiteByPageId($this->configuration->getLoginPage())->getRouter($this->context);

        if ($authorizationRequest === null) {
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

        $userIdentifier = (int)$this->context->getPropertyFromAspect('frontend.user', 'id');
        if ($userIdentifier <= 0) {
            throw new IdentifierEmptyException(
                'Logged-in frontend user has no uid.',
                1793110882
            );
        }
        $authorizationRequest->setUser(new User((string)$userIdentifier));
        $authorizationRequest->setAuthorizationApproved(true);

        $userSession->removeData('oauth2.authorizationRequest');

        try {
            return $server->completeAuthorizationRequest($authorizationRequest, new Response());
        } catch (OAuthServerException $e) {
            return $e->generateHttpResponse(new Response());
        }
    }

    /**
     * Returns the authorization request stored while the user logged in, if there is a valid one.
     */
    private function restoreAuthorizationRequest(UserSession $userSession): ?AuthorizationRequest
    {
        $storedRequest = $userSession->getData('oauth2.authorizationRequest');
        if (!is_string($storedRequest) || $storedRequest === '') {
            return null;
        }
        $authorizationRequest = unserialize($storedRequest, [
            'allowed_classes' => [
                AuthorizationRequest::class,
                Client::class,
                Scope::class,
            ],
        ]);
        if (!$authorizationRequest instanceof AuthorizationRequest) {
            $this->logger->error('Unserializing of AuthorizationRequest failed!');
            $userSession->removeData('oauth2.authorizationRequest');
            return null;
        }
        return $authorizationRequest;
    }
}
