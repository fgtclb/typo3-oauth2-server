<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Functional;

use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

class OauthServerIdentityTest extends AbstractOauth2ServerTest
{
    public function identityDataProvider(): \Generator
    {
        yield 'Logged-In user returns logged in content' => [
            'path' => '/oauth/identity',
            'feUserId' => self::FE_USER_ID,
            'authorizationBody' => [
                'grant_type' => 'authorization_code',
                'client_id' => 'acme_client',
                'client_secret' => 'password',
            ],
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
        array $authorizationBody
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
        $authorizationBody['code'] = $query['code'] ?? '';

        $authorizationUri = (new Uri(self::BASE_URL))
            ->withPath('/oauth/token');
        $authorizationResponse = $this->executeFrontendSubRequest(
            (new InternalRequest($authorizationUri))->withParsedBody($authorizationBody)
        );

        $authorizationResponse->getBody()->rewind();
        $authorizationBody = json_decode($authorizationResponse->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest($uri))->withAddedHeader('Authorization', 'Bearer ' . $authorizationBody['access_token'])
        );

        $response->getBody()->rewind();
        $responseBody = $response->getBody()->getContents();
    }
}
