<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Unit\Domain\Entity;

use FGTCLB\OAuth2Server\Domain\Entity\Client;
use FGTCLB\OAuth2Server\Exception\IdentifierEmptyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(Client::class)]
final class ClientTest extends UnitTestCase
{
    public static function invalidIdentifierDataProvider(): \Generator
    {
        yield 'empty string' => [
            'identifier' => '',
        ];
        yield 'string zero' => [
            'identifier' => '0',
        ];
    }

    #[Test]
    #[DataProvider('invalidIdentifierDataProvider')]
    public function fromDatabaseRowRejectsInvalidIdentifier(string $identifier): void
    {
        $this->expectException(IdentifierEmptyException::class);
        $this->expectExceptionCode(1793110430);

        Client::fromDatabaseRow([
            'uid' => 1,
            'identifier' => $identifier,
            'name' => 'Test client',
            'secret' => 'secret',
            'redirect_uris' => 'https://client.example.com/callback',
            'description' => '',
        ]);
    }

    public static function validIdentifierDataProvider(): \Generator
    {
        yield 'numeric string' => [
            'identifier' => '1',
        ];
        yield 'alphanumeric string' => [
            'identifier' => 'acme-client',
        ];
        yield 'uuid' => [
            'identifier' => '0c4f2b8e-6f1d-4c3a-9e57-2b1d8f3a7c90',
        ];
    }

    #[Test]
    #[DataProvider('validIdentifierDataProvider')]
    public function fromDatabaseRowAcceptsValidIdentifier(string $identifier): void
    {
        $client = Client::fromDatabaseRow([
            'uid' => 1,
            'identifier' => $identifier,
            'name' => 'Test client',
            'secret' => 'secret',
            'redirect_uris' => 'https://client.example.com/callback',
            'description' => '',
        ]);

        $this->assertSame($identifier, $client->getIdentifier());
    }
}
