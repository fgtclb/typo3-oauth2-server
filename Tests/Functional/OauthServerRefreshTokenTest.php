<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

final class OauthServerRefreshTokenTest extends AbstractOauth2ServerTestCase
{
    public static function accessTokenDataProvider(): \Generator
    {
        yield 'Logged-in user gets access token' => [
            'path' => '/oauth/token',
            'body' => [
                'grant_type' => 'refresh_token',
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
        //*
        yield 'Logged-in user with missing client_secret gets Bad Request' => [
            'path' => '/oauth/token',
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => 'acme_client',
            ],
            'feUserId' => self::FE_USER_ID,
            'expectedStatusCode' => 400,
            'expectedReasonPhrase' => 'Bad Request',
            'expectedTokenType' => '',
            'expectedResponseKeys' => [
                'error',
                'error_description',
                'hint',
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
            ],
        ];
        //*/
    }

    /**
     * @param array{grant_type: string, client_id: string, client_secret?: string} $body
     * @param string[] $expectedResponseKeys
     */
    #[Test]
    #[DataProvider('accessTokenDataProvider')]
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

        $authCodes = $this->getGeneratedTokens();

        $body['refresh_token'] = $authCodes['refresh_token'];
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest($uri))
                ->withParsedBody($body)
        );
        $response->getBody()->rewind();
        $responseBody = $response->getBody()->getContents();

        $responseData = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($expectedStatusCode, $response->getStatusCode());
        $this->assertSame($expectedReasonPhrase, $response->getReasonPhrase());
        $this->assertJson($responseBody);
        foreach ($expectedResponseKeys as $expectedKey) {
            $this->assertArrayHasKey($expectedKey, $responseData);
        }
        if (!empty($expectedTokenType)) {
            $this->assertSame($expectedTokenType, $responseData['token_type']);
        }
    }
}
