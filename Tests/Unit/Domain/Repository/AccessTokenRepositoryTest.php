<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Unit\Domain\Repository;

use FGTCLB\OAuth2Server\Domain\Repository\AccessTokenRepository;
use FGTCLB\OAuth2Server\Exception\IdentifierEmptyException;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @covers \FGTCLB\OAuth2Server\Domain\Repository\AccessTokenRepository
 */
final class AccessTokenRepositoryTest extends UnitTestCase
{
    public static function invalidUserIdentifierDataProvider(): \Generator
    {
        yield 'null' => [
            'userIdentifier' => null,
        ];
        yield 'empty string' => [
            'userIdentifier' => '',
        ];
        yield 'string zero' => [
            'userIdentifier' => '0',
        ];
    }

    /**
     * @test
     * @dataProvider invalidUserIdentifierDataProvider
     */
    public function getNewTokenRejectsInvalidUserIdentifier(?string $userIdentifier): void
    {
        $this->expectException(IdentifierEmptyException::class);
        $this->expectExceptionCode(1793110512);

        (new AccessTokenRepository())->getNewToken(
            $this->createStub(ClientEntityInterface::class),
            [],
            $userIdentifier
        );
    }

    public static function validUserIdentifierDataProvider(): \Generator
    {
        yield 'lowest uid' => [
            'userIdentifier' => '1',
        ];
        yield 'regular uid' => [
            'userIdentifier' => '42',
        ];
        yield 'non-numeric identifier' => [
            'userIdentifier' => 'jane.doe',
        ];
    }

    /**
     * @test
     * @dataProvider validUserIdentifierDataProvider
     */
    public function getNewTokenAcceptsValidUserIdentifier(string $userIdentifier): void
    {
        $client = $this->createStub(ClientEntityInterface::class);

        $accessToken = (new AccessTokenRepository())->getNewToken(
            $client,
            [],
            $userIdentifier
        );

        $this->assertSame($userIdentifier, $accessToken->getUserIdentifier());
        $this->assertSame($client, $accessToken->getClient());
    }
}
