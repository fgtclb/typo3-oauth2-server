<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Functional;

use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

class OAuthServerAuthorizationTest extends AbstractOauth2ServerTest
{
    /**
     * @return \Generator<string, array{
     *     path: string,
     *     query: string,
     *     feUserId: int,
     *     expectedReasonPhrase: string,
     *     expectedStatusCode: int,
     *     expectedHeaderArrayKey: string,
     *     expectedRedirectStringStart: string
     * }>
     */
    public function oauthServerAuthorizationDataProvider(): \Generator
    {
        yield 'Access granted with frontend user logged in' => [
            'path' => '/oauth/authorize',
            'query' => '?response_type=code&client_id=acme_client',
            'feUserId' => self::FE_USER_ID,
            'expectedReasonPhrase' => 'Found',
            'expectedStatusCode' => 302,
            'expectedHeaderArrayKey' => 'Location',
            'expectedRedirectStringStart' => 'https://localhost/authenticated/?code=',
        ];
        yield 'Login Redirect with frontend user not logged in' => [
            'path' => '/oauth/authorize',
            'query' => '?response_type=code&client_id=acme_client',
            'feUserId' => 0,
            'expectedReasonPhrase' => 'Found',
            'expectedStatusCode' => 302,
            'expectedHeaderArrayKey' => 'Location',
            'expectedRedirectStringStart' => 'https://localhost/login?',
        ];
        yield 'Wrong client redirects to login page' => [
            'path' => '/oauth/authorize',
            'query' => '?response_type=code&client_id=wrong',
            'feUserId' => self::FE_USER_ID,
            'expectedReasonPhrase' => 'Found',
            'expectedStatusCode' => 302,
            'expectedHeaderArrayKey' => 'Location',
            'expectedRedirectStringStart' => 'https://localhost/login',
        ];
    }

    /**
     * @test
     * @dataProvider oauthServerAuthorizationDataProvider
     */
    public function oauthServerAuthorizationScenarios(
        string $path,
        string $query,
        int $feUserId,
        string $expectedReasonPhrase,
        int $expectedStatusCode,
        string $expectedHeaderArrayKey,
        string $expectedRedirectStringStart
    ): void {
        $uri = (string)(new Uri(self::BASE_URL))
            ->withPath($path)
            ->withQuery($query);

        $internalRequestContext = (new InternalRequestContext())
            ->withFrontendUserId($feUserId);

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest($uri)),
            $internalRequestContext
        );
        self::assertSame($expectedReasonPhrase, $response->getReasonPhrase());
        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertIsArray($response->getHeader($expectedHeaderArrayKey));
        self::assertStringStartsWith($expectedRedirectStringStart, $response->getHeader($expectedHeaderArrayKey)[0]);
    }
}
