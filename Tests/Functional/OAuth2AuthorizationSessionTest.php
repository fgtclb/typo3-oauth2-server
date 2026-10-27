<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Functional;

use FGTCLB\OAuth2Server\Middleware\OAuth2Authorization;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Covers the authorization request stored in the frontend session while the user logs in.
 */
final class OAuth2AuthorizationSessionTest extends AbstractOauth2ServerTestCase
{
    private const SESSION_KEY = 'oauth2.authorizationRequest';

    #[Test]
    public function storedAuthorizationRequestIsSerializedOnceOnRepeatedAnonymousVisits(): void
    {
        $frontendUser = $this->createAnonymousFrontendUser();
        $subject = $this->get(OAuth2Authorization::class);

        $subject->process(
            (new ServerRequest('https://localhost/oauth/authorize?response_type=code&client_id=acme_client', 'GET'))
                ->withQueryParams(['response_type' => 'code', 'client_id' => 'acme_client'])
                ->withAttribute('frontend.user', $frontendUser),
            $this->createFallbackHandler()
        );
        // The login page redirects back to the endpoint without the original query.
        $response = $subject->process(
            (new ServerRequest('https://localhost/oauth/authorize', 'GET'))
                ->withAttribute('frontend.user', $frontendUser),
            $this->createFallbackHandler()
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringStartsWith('https://localhost/login?', $response->getHeaderLine('Location'));
        $storedRequest = $frontendUser->getSessionData(self::SESSION_KEY);
        $this->assertIsString($storedRequest);
        $this->assertInstanceOf(
            AuthorizationRequest::class,
            unserialize($storedRequest, ['allowed_classes' => true])
        );
    }

    #[Test]
    public function storedAuthorizationRequestIsCompletedAfterLogin(): void
    {
        $frontendUser = $this->createAnonymousFrontendUser();
        $subject = $this->get(OAuth2Authorization::class);
        $subject->process(
            (new ServerRequest('https://localhost/oauth/authorize?response_type=code&client_id=acme_client', 'GET'))
                ->withQueryParams(['response_type' => 'code', 'client_id' => 'acme_client'])
                ->withAttribute('frontend.user', $frontendUser),
            $this->createFallbackHandler()
        );
        $subject->process(
            (new ServerRequest('https://localhost/oauth/authorize', 'GET'))
                ->withAttribute('frontend.user', $frontendUser),
            $this->createFallbackHandler()
        );

        $frontendUser->user = [
            'uid' => self::FE_USER_ID,
            'username' => 'ACME Frontend User',
        ];
        $this->get(Context::class)->setAspect('frontend.user', new UserAspect($frontendUser));
        $response = $subject->process(
            (new ServerRequest('https://localhost/oauth/authorize', 'GET'))
                ->withAttribute('frontend.user', $frontendUser),
            $this->createFallbackHandler()
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringStartsWith('https://localhost/authenticated/?code=', $response->getHeaderLine('Location'));
        $this->assertSame('', $frontendUser->getSessionData(self::SESSION_KEY) ?? '');
    }

    private function createAnonymousFrontendUser(): FrontendUserAuthentication
    {
        $frontendUser = new FrontendUserAuthentication();
        $frontendUser->initializeUserSessionManager();
        $this->get(Context::class)->setAspect('frontend.user', new UserAspect($frontendUser));
        return $frontendUser;
    }

    private function createFallbackHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };
    }
}
