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

        // we need to change file permission s here as League/OAuth2 will check for right permissions
        chmod(GeneralUtility::getFileAbsFileName(self::PUBLIC_KEY_FILE_PATH), 0600);
        chmod(GeneralUtility::getFileAbsFileName(self::PRIVATE_KEY_FILE_PATH), 0600);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/oauth.csv');
    }

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
    public function oauthServerDataProvider(): \Generator
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
     * @dataProvider oauthServerDataProvider
     */
    public function oauthServerAccessScenarios(
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
