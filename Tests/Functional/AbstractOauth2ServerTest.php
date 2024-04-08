<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Functional;

use FGTCLB\Testsuite\TestCase\AbstractSiteCest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractOauth2ServerTest extends AbstractSiteCest
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
}
