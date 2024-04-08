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

    public function validConfigurationDataProvider(): \Generator
    {
        yield 'Basic configuration' => [
            'configurationArrayToTest' => [
                'privateKeyFile' => 'private.key',
                'publicKeyFile' => 'public.key',
                'loginPage' => '10',
                'authEndpoint' => '/oauth/authorize',
                'tokenEndpoint' => '/oauth/token',
                'resourceEndpoint' => '/oauth/identity',
                'accessTokenLifetime' => '1 hour',
                'refreshTokenLifetime' => '1 month',
                'authorizationCodeLifetime' => '10 minutes',
            ],
            'expectedPrivateKeyString' => 'private.key',
            'expectedPublicKeyString' => 'public.key',
            'expectedLoginPage' => 10,
            'expectedAuthEndpoint' => '/oauth/authorize',
            'expectedTokenEndpoint' => '/oauth/token',
            'expectedResourceEndpoint' => '/oauth/identity',
            'expectedAccessTokenLifetimeType' => \DateInterval::class,
            'expectedRefreshTokenLifetimeType' => \DateInterval::class,
            'expectedAuthorizationCodeLifetimeType' => \DateInterval::class,
        ];
    }
    /**
     * @test
     * @param array<string, string> $configurationArrayToTest
     * @param class-string $expectedAccessTokenLifetimeType
     * @param class-string $expectedRefreshTokenLifetimeType
     * @param class-string $expectedAuthorizationCodeLifetimeType
     * @dataProvider validConfigurationDataProvider
     */
    public function acceptsValidConfiguration(
        array $configurationArrayToTest,
        string $expectedPrivateKeyString,
        string $expectedPublicKeyString,
        int $expectedLoginPage,
        string $expectedAuthEndpoint,
        string $expectedTokenEndpoint,
        string $expectedResourceEndpoint,
        string $expectedAccessTokenLifetimeType,
        string $expectedRefreshTokenLifetimeType,
        string $expectedAuthorizationCodeLifetimeType
    ): void {
        /** @var TProphecy $extensionConfiguration */
        $extensionConfiguration = $this->prophesize(ExtensionConfiguration::class);
        $extensionConfiguration->get('oauth2_server')->willReturn($configurationArrayToTest);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfiguration->reveal());

        $configuration = new Configuration();

        self::assertStringEndsWith($expectedPrivateKeyString, $configuration->getPrivateKeyFile());
        self::assertStringEndsWith($expectedPublicKeyString, $configuration->getPublicKeyFile());
        self::assertEquals($expectedLoginPage, $configuration->getLoginPage());
        self::assertEquals($expectedAuthEndpoint, $configuration->getAuthEndpoint());
        self::assertEquals($expectedTokenEndpoint, $configuration->getTokenEndpoint());
        self::assertEquals($expectedResourceEndpoint, $configuration->getResourceEndpoint());
        self::assertInstanceOf($expectedAccessTokenLifetimeType, $configuration->getAccessTokenLifetime());
        self::assertInstanceOf($expectedRefreshTokenLifetimeType, $configuration->getRefreshTokenLifetime());
        self::assertInstanceOf($expectedAuthorizationCodeLifetimeType, $configuration->getAuthorizationCodeLifetime());
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
    ): void {
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
