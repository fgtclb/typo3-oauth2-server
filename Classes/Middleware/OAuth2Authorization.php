<?php
declare(strict_types = 1);

namespace FGTCLB\OAuth2Server\Middleware;

use FGTCLB\OAuth2Server\Server\ServerFactory;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\Response;

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

        try {
            $authorizationRequest = $server->validateAuthorizationRequest($request);

            /** @var Context */
            $context = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Context::class);

            if (!$context->getPropertyFromAspect('frontend.user', 'isLoggedIn', false)) {
                // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($GLOBALS['TSFE']->fe_user, __METHOD__, 8, defined('TYPO3_cliMode') || defined('TYPO3_REQUESTTYPE') && (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI));
                throw new \Exception("Error Processing Request", 1);
            }

            $user = new class implements \League\OAuth2\Server\Entities\UserEntityInterface {
                use \League\OAuth2\Server\Entities\Traits\EntityTrait;
            };
            $user->setIdentifier('test');
            $authorizationRequest->setUser($user);
            $authorizationRequest->setAuthorizationApproved(true);

            return $server->completeAuthorizationRequest($authorizationRequest, new Response());
        } catch (OAuthServerException $e) {
            return $e->generateHttpResponse(new Response());
        }

        return (new Response())->withStatus(500);
    }
}
