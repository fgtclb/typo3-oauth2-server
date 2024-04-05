<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Functional;

use FGTCLB\Testsuite\TestCase\AbstractSiteCest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

class OAuthServerTest extends AbstractSiteCest
{
    private const FE_USER_ID = 1;

    private const PRIVATE_KEY_FILE_PATH = 'typ3temp/var/transient/ssh/test_key';
    private const PUBLIC_KEY_FILE_PATH = 'typ3temp/var/transient/ssh/test_key.pub';

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
        '../../../../../../Tests/Functional/Fixtures/ssh/' => 'typ3temp/var/transient/ssh/',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        chmod(GeneralUtility::getFileAbsFileName(self::PUBLIC_KEY_FILE_PATH), 0600);
        chmod(GeneralUtility::getFileAbsFileName(self::PRIVATE_KEY_FILE_PATH), 0600);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/oauth.csv');
    }

    /**
     * @test
     */
    public function oauthServerGrantsAccessWithFrontendUserLoggedIn(): void
    {
        $uri = (string)(new Uri(self::BASE_URL))
            ->withPath('/oauth/authorize')
            ->withQuery('?response_type=code&client_id=acme_client');

        $internalRequestContext = (new InternalRequestContext())
            ->withFrontendUserId(self::FE_USER_ID);

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest($uri)),
            $internalRequestContext
        );
        self::assertSame('Found', $response->getReasonPhrase());
        self::assertSame(302, $response->getStatusCode());
        self::assertIsArray($response->getHeader('Location'));
        self::assertStringStartsWith('https://localhost/authenticated/?code=', $response->getHeader('Location')[0]);
    }

    /**
     * @test
     */
    public function oauthServerRedirectsToLoginWithNotLoggedInFrontendUser(): void
    {
        $uri = (string)(new Uri(self::BASE_URL))
            ->withPath('/oauth/authorize')
            ->withQuery('?response_type=code&client_id=acme_client');

        $internalRequestContext = (new InternalRequestContext());

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest($uri)),
            $internalRequestContext
        );
        self::assertSame('Found', $response->getReasonPhrase());
        self::assertSame(302, $response->getStatusCode());
        self::assertIsArray($response->getHeader('Location'));
        self::assertStringStartsWith('https://localhost/login', $response->getHeader('Location')[0]);
    }
}
