<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Unit\Server;

use FGTCLB\OAuth2Server\Exception\MissingConfigurationException;
use FGTCLB\OAuth2Server\Server\ServerConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(ServerConfiguration::class)]
final class ServerConfigurationTest extends UnitTestCase
{
    #[Test]
    public function acceptsValidConfiguration(): void
    {
        $extensionConfiguration = $this->createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturnMap([
            ['oauth2_server', '', ['privateKeyFile' => 'private.key', 'publicKeyFile' => 'public.key']],
        ]);

        $subject = new ServerConfiguration($extensionConfiguration);

        $this->assertSame('private.key', $subject->getPrivateKeyFile());
        $this->assertSame('public.key', $subject->getPublicKeyFile());
    }

    public static function invalidConfigurationDataProvider(): \Generator
    {
        yield 'missing private key file' => [
            'configuration' => [
                'publicKeyFile' => 'public.key',
            ],
            'expectedExceptionCode' => 1559054947,
        ];
        yield 'missing public key file' => [
            'configuration' => [
                'privateKeyFile' => 'private.key',
            ],
            'expectedExceptionCode' => 1559054961,
        ];
    }

    /**
     * @param array<string, string> $configuration
     */
    #[Test]
    #[DataProvider('invalidConfigurationDataProvider')]
    public function rejectsInvalidConfiguration(array $configuration, int $expectedExceptionCode): void
    {
        $extensionConfiguration = $this->createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturnMap([
            ['oauth2_server', '', $configuration],
        ]);

        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode($expectedExceptionCode);

        new ServerConfiguration($extensionConfiguration);
    }
}
