<?php
declare(strict_types = 1);

namespace FGTCLB\OAuth2Server\Middleware;

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
 */
final class OAuth2Authorization implements MiddlewareInterface
{
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

        // Ensure FE user groups are loaded and URLs can be generated
        $this->bootFrontendController();

        /** @var Context */
        $context = GeneralUtility::makeInstance(Context::class);

        if (!$context->getPropertyFromAspect('frontend.user', 'isLoggedIn', false)) {
            $userSession->setData('oauth2.authorizationRequest', $authorizationRequest);

            /** @var ContentObjectRenderer */
            $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $redirectUri = $contentObjectRenderer->typoLink_URL([
                'parameter' => sprintf('t3://page?uid=%d&redirect_url=%s', 1, '/oauth/authorize'),
            ]);

            return new RedirectResponse($redirectUri);
        }

        $user = new class implements \League\OAuth2\Server\Entities\UserEntityInterface {
            use \League\OAuth2\Server\Entities\Traits\EntityTrait;
        };
        $user->setIdentifier('test');
        $authorizationRequest->setUser($user);
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
     * So a link to a page could be generated.
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
