<?php

declare(strict_types=1);

namespace FGTCLB\Testsuite\TestCase;

use FGTCLB\Testsuite\Traits\SiteBasedTestTrait;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class AbstractSiteTestCase extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [];

    protected const BASE_URL = 'https://localhost/';
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordHashing']['className'] = Argon2iPasswordHash::class;

        $this->writeSiteConfiguration(
            'fcp-local',
            $this->buildSiteConfiguration(
                1,
                self::BASE_URL,
                'FES Community-Portal'
            )
        );
    }
}
