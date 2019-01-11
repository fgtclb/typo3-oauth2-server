<?php
declare(strict_types = 1);

namespace FGTCLB\OAuth2Server\Middleware;

use FGTCLB\OAuth2Server\Configuration;
use FGTCLB\OAuth2Server\Domain\Entity\User;
use FGTCLB\OAuth2Server\Server\ServerFactory;
use FGTCLB\OAuth2Server\Session\UserSession;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @param Configuration|null $configuration
     */
    public function __construct(Configuration $configuration = null)
    {
        $this->configuration = $configuration ?: GeneralUtility::makeInstance(Configuration::class);
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

        $userSession = new UserSession();
        $authorizationRequest = $userSession->getData('oauth2.authorizationRequest');

        if (!$authorizationRequest) {
            try {
                $authorizationRequest = $server->validateAuthorizationRequest($request);
            } catch (OAuthServerException $e) {
                return $e->generateHttpResponse(new Response());
            }
        }

        $this->bootFrontendController();

        /** @var Context */
        $context = GeneralUtility::makeInstance(Context::class);

        if (!$context->getPropertyFromAspect('frontend.user', 'isLoggedIn', false)) {
            $userSession->setData('oauth2.authorizationRequest', $authorizationRequest);

            /** @var ContentObjectRenderer */
            $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $redirectUri = $contentObjectRenderer->typoLink_URL([
                'parameter' => sprintf('t3://page?uid=%d&redirect_url=%s', $this->configuration->getLoginPage(), $request->getUri()->getPath()),
            ]);

            return new RedirectResponse($redirectUri);
        }

        $authorizationRequest->setUser(new User((string)$context->getPropertyFromAspect('frontend.user', 'id')));
        $authorizationRequest->setAuthorizationApproved(true);

        $userSession->removeData('oauth2.authorizationRequest');

        try {
            return $server->completeAuthorizationRequest($authorizationRequest, new Response());
        } catch (OAuthServerException $e) {
            return $e->generateHttpResponse(new Response());
        }

        return (new Response())->withStatus(500);
    }

    /**
     * Finishing booting up TSFE, after that the following properties are available:
     *
     * - TSFE->fe_user
     * - TSFE->sys_page
     * - TSFE->tmpl
     * - TSFE->config
     * - TSFE->cObj
     *
     * This ensures FE user groups are loaded and URLs can be generated.
     */
    protected function bootFrontendController()
    {
        // disable page errors
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling'] = false;
        $GLOBALS['TSFE']->fetch_the_id();
        $GLOBALS['TSFE']->getConfigArray();
        $GLOBALS['TSFE']->settingLanguage();
        $GLOBALS['TSFE']->settingLocale();
        $GLOBALS['TSFE']->newCObj();
    }
}
