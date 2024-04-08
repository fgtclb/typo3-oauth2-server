<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Functional;

use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

class OauthServerAccessTokenTest extends AbstractOauth2ServerTest
{
    public function accessTokenDataProvider(): \Generator
    {
        yield 'Logged-in user gets access token' => [
            'path' => '/oauth/token',
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => 'acme_client',
                'client_secret' => 'password',
            ],
            'feUserId' => self::FE_USER_ID,
            'expectedStatusCode' => 200,
            'expectedReasonPhrase' => 'OK',
            'expectedTokenType' => 'Bearer',
            'expectedResponseKeys' => [
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
            ],
        ];

        yield 'Logged-in user with missing client_secret gets Unauthorized' => [
            'path' => '/oauth/token',
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => 'acme_client',
            ],
            'feUserId' => self::FE_USER_ID,
            'expectedStatusCode' => 401,
            'expectedReasonPhrase' => 'Unauthorized',
            'expectedTokenType' => '',
            'expectedResponseKeys' => [
                'error',
                'error_description',
                'message',
            ],
        ];

        yield 'Logged-in user with wrong client_secret gets Unauthorized' => [
            'path' => '/oauth/token',
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => 'acme_client',
                'client_secret' => 'wrong_secret',
            ],
            'feUserId' => self::FE_USER_ID,
            'expectedStatusCode' => 401,
            'expectedReasonPhrase' => 'Unauthorized',
            'expectedTokenType' => '',
            'expectedResponseKeys' => [
                'error',
                'error_description',
                'message',
            ],
        ];

        yield 'Logged-in user with wrong client_id gets Unauthorized' => [
            'path' => '/oauth/token',
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => 'wrong_client',
                'client_secret' => 'wrong_secret',
            ],
            'feUserId' => self::FE_USER_ID,
            'expectedStatusCode' => 401,
            'expectedReasonPhrase' => 'Unauthorized',
            'expectedTokenType' => '',
            'expectedResponseKeys' => [
                'error',
                'error_description',
                'message',
            ],
        ];
    }
    /**
     * @test
     * @param array{grant_type: string, client_id: string, client_secret?: string} $body
     * @param string[] $expectedResponseKeys
     * @dataProvider accessTokenDataProvider
     */
    public function oauthServerAccessTokenScenarios(
        string $path,
        array $body,
        int $feUserId,
        int $expectedStatusCode,
        string $expectedReasonPhrase,
        string $expectedTokenType,
        array $expectedResponseKeys
    ): void {
        $uri = (string)(new Uri(self::BASE_URL))
            ->withPath($path);

        $internalRequestContext = (new InternalRequestContext())
            ->withFrontendUserId($feUserId);

        $loginUri = (string)(new Uri(self::BASE_URL))
            ->withPath('/oauth/authorize')
            ->withQuery('?response_type=code&client_id=acme_client');
        $loginResponse = $this->executeFrontendSubRequest(
            (new InternalRequest($loginUri)),
            $internalRequestContext
        );
        $redirectUri = $loginResponse->getHeader('Location')[0];
        parse_str(parse_url($redirectUri, PHP_URL_QUERY) ?: '', $query);
        $body['code'] = $query['code'] ?? '';
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest($uri))
                ->withParsedBody($body),
            $internalRequestContext
        );
        $response->getBody()->rewind();
        $responseBody = $response->getBody()->getContents();

        $responseData = json_decode($responseBody, true, JSON_THROW_ON_ERROR);

        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertSame($expectedReasonPhrase, $response->getReasonPhrase());
        self::assertJson($responseBody);
        foreach ($expectedResponseKeys as $expectedKey) {
            self::assertArrayHasKey($expectedKey, $responseData);
        }
        if (!empty($expectedTokenType)) {
            self::assertSame($expectedTokenType, $responseData['token_type']);
        }
    }
}
