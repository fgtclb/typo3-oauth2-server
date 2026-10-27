<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Unit;

use FGTCLB\OAuth2Server\Configuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(Configuration::class)]
class ConfigurationTest extends UnitTestCase
{
    /**
     * Tear down this testcase
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    public static function validConfigurationDataProvider(): \Generator
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
     * @param array<string, string> $configurationArrayToTest
     * @param non-empty-string $expectedPrivateKeyString
     * @param non-empty-string $expectedPublicKeyString
     * @param class-string $expectedAccessTokenLifetimeType
     * @param class-string $expectedRefreshTokenLifetimeType
     * @param class-string $expectedAuthorizationCodeLifetimeType
     */
    #[Test]
    #[DataProvider('validConfigurationDataProvider')]
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
        $extensionConfiguration = $this->createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturnMap([
            ['oauth2_server', '', $configurationArrayToTest],
        ]);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfiguration);

        $configuration = new Configuration();

        $this->assertStringEndsWith($expectedPrivateKeyString, $configuration->getPrivateKeyFile());
        $this->assertStringEndsWith($expectedPublicKeyString, $configuration->getPublicKeyFile());
        $this->assertEquals($expectedLoginPage, $configuration->getLoginPage());
        $this->assertEquals($expectedAuthEndpoint, $configuration->getAuthEndpoint());
        $this->assertEquals($expectedTokenEndpoint, $configuration->getTokenEndpoint());
        $this->assertEquals($expectedResourceEndpoint, $configuration->getResourceEndpoint());
        $this->assertInstanceOf($expectedAccessTokenLifetimeType, $configuration->getAccessTokenLifetime());
        $this->assertInstanceOf($expectedRefreshTokenLifetimeType, $configuration->getRefreshTokenLifetime());
        $this->assertInstanceOf($expectedAuthorizationCodeLifetimeType, $configuration->getAuthorizationCodeLifetime());
    }

    /**
     * @param array{
     *     privateKeyFile?: string,
     *     publicKeyFile?: string,
     *     loginPage?: string|int
     * } $invalidExtensionConfiguration
     */
    #[Test]
    #[DataProvider('invalidExtensionConfiguration')]
    public function rejectsInvalidConfiguration(
        array $invalidExtensionConfiguration,
        int $expectedExceptionCode,
        string $expectedExceptionMessage
    ): void {
        $extensionConfiguration = $this->createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturnMap([
            ['oauth2_server', '', $invalidExtensionConfiguration],
        ]);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfiguration);

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
    public static function invalidExtensionConfiguration(): \Generator
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
