<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Domain\Repository;

use FGTCLB\OAuth2Server\Domain\Entity\Client;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Repository for OAuth2 clients.
 *
 * This does not follow TYPO3's usual naming conventions for repositories because it implements an interface from
 * league/oauth2-server.
 *
 * @phpstan-type TClientRow array{uid: int, identifier: string, name: string, secret: string, redirect_uris: string, description: string}
 */
final class ClientRepository implements ClientRepositoryInterface
{
    private const TABLE_NAME = 'tx_oauth2server_client';
    private readonly Connection $databaseConnection;

    public function __construct(
        private readonly PasswordHashFactory $hashFactory,
        ConnectionPool $connectionPool,
        private readonly LoggerInterface $logger
    ) {
        $this->databaseConnection = $connectionPool->getConnectionForTable(self::TABLE_NAME);
    }

    public function getClientEntity(string $clientIdentifier): ?Client
    {
        $clientData = $this->findRawByIdentifier($clientIdentifier);
        if (!$clientData) {
            return null;
        }

        return Client::fromDatabaseRow($clientData);
    }

    /**
     * @return TClientRow|null
     */
    private function findRawByIdentifier(string $clientIdentifier): ?array
    {
        $queryBuilder = $this->databaseConnection
            ->createQueryBuilder();
        $result = $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($clientIdentifier))
            )
            ->setMaxResults(1)
            ->executeQuery();
        /** @var TClientRow|false $row */
        $row = $result->fetchAssociative();
        return $row ?: null;
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        // we currently only support confidential clients, which must always supply a secret (TODO check if this is actually true).
        if ($clientSecret === null) {
            return false;
        }

        $client = $this->getClientEntity($clientIdentifier);
        if ($client === null) {
            return false;
        }

        $result = $client->validateSecret($clientSecret, $this->hashFactory);
        if ($result === false) {
            $this->logger->debug(sprintf('Submitted secret is invalid for client %s', $clientIdentifier));
        }
        return $result;
    }
}
