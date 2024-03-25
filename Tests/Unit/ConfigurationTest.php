<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Unit;

use FGTCLB\OAuth2Server\Configuration;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @covers \FGTCLB\OAuth2Server\Configuration
 * @template TProphecy of ExtensionConfiguration|ObjectProphecy
 */
class ConfigurationTest extends UnitTestCase
{
    use ProphecyTrait;
    /**
     * Tear down this testcase
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    /**
     * @test
     */
    public function acceptsValidConfiguration(): void
    {
        /** @var TProphecy $extensionConfiguration */
        $extensionConfiguration = $this->prophesize(ExtensionConfiguration::class);
        $extensionConfiguration->get('oauth2_server')->willReturn([
            'privateKeyFile' => 'private.key',
            'publicKeyFile' => 'public.key',
            'loginPage' => '10',
        ]);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfiguration->reveal());

        $configuration = new Configuration();

        self::assertEquals('private.key', $configuration->getPrivateKeyFile());
        self::assertEquals('public.key', $configuration->getPublicKeyFile());
        self::assertEquals(10, $configuration->getLoginPage());
    }

    /**
     * @test
     * @dataProvider invalidExtensionConfiguration
     * @param array{privateKeyFile?: string, publicKeyFile?: string, loginPage?: string|int} $invalidExtensionConfiguration
     */
    public function rejectsInvalidConfiguration(
        array $invalidExtensionConfiguration,
        int $expectedExceptionCode,
        string $expectedExceptionMessage
    ): void
    {
        /** @var TProphecy $extensionConfiguration */
        $extensionConfiguration = $this->prophesize(ExtensionConfiguration::class);
        $extensionConfiguration->get('oauth2_server')->willReturn($invalidExtensionConfiguration);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfiguration->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedExceptionCode);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $configuration = new Configuration();
    }

    /**
     * @return \Generator<string, array{
     *     invalidExtensionConfiguration: array{
     *         privateKeyFile?: string,
     *         publicKeyFile?: string,
     *         loginPage?: string|int
     *     },
     *     expectedExceptionCode: int,
     *     expectedExceptionMessage: string
     *     }>
     */
    public function invalidExtensionConfiguration(): \Generator
    {
        yield 'Missing private key leads to Exception' => [
            'invalidExtensionConfiguration' => [
                'publicKeyFile' => 'public.key',
                'loginPage' => '10',
            ],
            'expectedExceptionCode' => 1539686145,
            'expectedExceptionMessage' => 'Missing "privateKeyFile" in OAuth2 server extension configuration',
        ];

        yield 'Missing public key leads to Exception' => [
            'invalidExtensionConfiguration' => [
                'privateKeyFile' => 'private.key',
                'loginPage' => '10',
            ],
            'expectedExceptionCode' => 1539686197,
            'expectedExceptionMessage' => 'Missing "publicKeyFile" in OAuth2 server extension configuration',
        ];

        yield 'Missing login page leads to Exception' => [
            'invalidExtensionConfiguration' => [
                'privateKeyFile' => 'private.key',
                'publicKeyFile' => 'public.key',
            ],
            'expectedExceptionCode' => 1539693234,
            'expectedExceptionMessage' => 'Missing/invalid "loginPage" in OAuth2 server extension configuration',
        ];

        yield 'invalid login page' => [
            'invalidExtensionConfiguration' => [
                'privateKeyFile' => 'private.key',
                'publicKeyFile' => 'public.key',
                'loginPage' => 0,
            ],
            'expectedExceptionCode' => 1539693234,
            'expectedExceptionMessage' => 'Missing/invalid "loginPage" in OAuth2 server extension configuration',
        ];
    }
}
