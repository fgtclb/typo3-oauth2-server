<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Functional;

use FGTCLB\Testsuite\TestCase\AbstractSiteTestCase;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

abstract class AbstractOauth2ServerTestCase extends AbstractSiteTestCase
{
    protected const FE_USER_ID = 1;

    protected const PRIVATE_KEY_FILE_PATH = 'typ3temp/var/transient/ssh/test_key';

    protected const PUBLIC_KEY_FILE_PATH = 'typ3temp/var/transient/ssh/test_key.pub';

    protected array $coreExtensionsToLoad = [
        'typo3/cms-core',
        'typo3/cms-frontend',
    ];

    protected array $testExtensionsToLoad = [
        '../../Tests/Functional/Extensions/testsuite',
        'fgtclb/oauth2-server',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'oauth2_server' => [
                'loginPage' => 3,
                'privateKeyFile' => self::PRIVATE_KEY_FILE_PATH,
                'publicKeyFile' => self::PUBLIC_KEY_FILE_PATH,
            ],
        ],
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/oauth2_server/Tests/Functional/Fixtures/ssh/' => 'typ3temp/var/transient/ssh/',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // we need to change file permission s here as League/OAuth2 will check for right permissions
        chmod(GeneralUtility::getFileAbsFileName(self::PUBLIC_KEY_FILE_PATH), 0600);
        chmod(GeneralUtility::getFileAbsFileName(self::PRIVATE_KEY_FILE_PATH), 0600);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/oauth.csv');
    }

    /**
     * Prepares authorization and returns the authentication code.
     *
     * @return string The authentication code.
     */
    final protected function prepareAuthorizationAndReturnAuthCode(): string
    {
        $internalRequestContext = (new InternalRequestContext())
            ->withFrontendUserId(self::FE_USER_ID);

        $loginUri = (string)(new Uri(self::BASE_URL))
            ->withPath('/oauth/authorize')
            ->withQuery('?response_type=code&client_id=acme_client');
        $loginResponse = $this->executeFrontendSubRequest(
            (new InternalRequest($loginUri)),
            $internalRequestContext
        );
        $redirectUri = $loginResponse->getHeader('Location')[0];
        parse_str(parse_url($redirectUri, PHP_URL_QUERY) ?: '', $query);

        return is_string($query['code']) ? $query['code'] : '';
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     * @throws \JsonException
     */
    final protected function getGeneratedTokens(): array
    {
        $authCode = $this->prepareAuthorizationAndReturnAuthCode();
        $uri = (string)(new Uri(self::BASE_URL))
            ->withPath('/oauth/token');
        $authBody = [
            'grant_type' => 'authorization_code',
            'client_id' => 'acme_client',
            'client_secret' => 'password',
            'code' => $authCode,
        ];
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest($uri))
                ->withParsedBody($authBody)
        );
        $response->getBody()->rewind();
        $responseBody = $response->getBody()->getContents();

        return json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
    }
}
