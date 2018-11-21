<?php
declare(strict_types = 1);

namespace FGTCLB\OAuth2Server\Tests\Unit;

use FGTCLB\OAuth2Server\Configuration;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for FGTCLB\OAuth2Server\Configuration
 */
class ConfigurationTest extends UnitTestCase
{
    /**
     * Tear down this testcase
     */
    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
    }

    /**
     * @test
     */
    public function acceptsValidConfiguration()
    {
        /** @var ExtensionConfiguration|\Prophecy\Prophecy\ObjectProphecy */
        $extensionConfiguration = $this->prophesize(ExtensionConfiguration::class);
        $extensionConfiguration->get('oauth2_server')->willReturn([
            'privateKeyFile' => 'private.key',
            'publicKeyFile' => 'public.key',
            'loginPage' => '10',
        ]);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfiguration->reveal());

        $configuration = new Configuration();

        $this->assertEquals('private.key', $configuration->getPrivateKeyFile());
        $this->assertEquals('public.key', $configuration->getPublicKeyFile());
        $this->assertEquals(10, $configuration->getLoginPage());
    }

    /**
     * @test
     * @dataProvider invalidExtensionConfiguration
     */
    public function rejectsInvalidConfiguration(array $invalidExtensionConfiguration)
    {
        /** @var ExtensionConfiguration|\Prophecy\Prophecy\ObjectProphecy */
        $extensionConfiguration = $this->prophesize(ExtensionConfiguration::class);
        $extensionConfiguration->get('oauth2_server')->willReturn($invalidExtensionConfiguration);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfiguration->reveal());

        $this->expectException(\InvalidArgumentException::class);

        $configuration = new Configuration();
    }

    public function invalidExtensionConfiguration(): \Generator
    {
        yield 'missing private key' => [
            [
                'publicKeyFile' => 'public.key',
                'loginPage' => '10',
            ],
        ];

        yield 'missing public key' => [
            [
                'privateKeyFile' => 'private.key',
                'loginPage' => '10',
            ],
        ];

        yield 'missing login page' => [
            [
                'privateKeyFile' => 'private.key',
                'publicKeyFile' => 'public.key',
            ],
        ];

        yield 'invalid login page' => [
            [
                'privateKeyFile' => 'private.key',
                'publicKeyFile' => 'public.key',
                'loginPage' => 0,
            ],
        ];
    }
}
