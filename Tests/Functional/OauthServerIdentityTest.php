<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Functional;

use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class OauthServerIdentityTest extends AbstractOauth2ServerTestCase
{
    public static function identityDataProvider(): \Generator
    {
        yield 'Logged-In user returns logged in content' => [
            'path' => '/oauth/identity',
            'feUserId' => self::FE_USER_ID,
            'authorizationBody' => [
                'grant_type' => 'authorization_code',
                'client_id' => 'acme_client',
                'client_secret' => 'password',
            ],
            'expectedReasonPhrase' => 'OK',
            'expectedStatusCode' => 200,
            'expectedJsonString' => '{"user_id":1,"username":"ACME Frontend User"}',
        ];
    }
    /**
     * @test
     * @param array{grant_type: string, client_id: string, client_secret?: string} $authorizationBody
     * @dataProvider identityDataProvider
     */
    public function oauthIdentityScenarios(
        string $path,
        int $feUserId,
        array $authorizationBody,
        string $expectedReasonPhrase,
        int $expectedStatusCode,
        string $expectedJsonString
    ): void {
        $uri = (string)(new Uri(self::BASE_URL))
            ->withPath($path);

        $authorizationBody = $this->getGeneratedTokens();

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest($uri))->withAddedHeader('Authorization', 'Bearer ' . $authorizationBody['access_token'])
        );

        $response->getBody()->rewind();
        $responseBody = $response->getBody()->getContents();

        self::assertEquals($expectedStatusCode, $response->getStatusCode());
        self::assertEquals($expectedReasonPhrase, $response->getReasonPhrase());
        self::assertJson($responseBody);
        self::assertEquals($expectedJsonString, $responseBody);
    }
}
