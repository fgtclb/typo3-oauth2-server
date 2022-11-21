<?php
declare(strict_types = 1);

namespace FGTCLB\OAuth2Server\Domain\Repository;

use FGTCLB\OAuth2Server\Domain\Entity\Client;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for OAuth2 clients
 */
final class ClientRepository implements ClientRepositoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const TABLE_NAME = 'tx_oauth2server_client';
    private PasswordHashFactory $hashFactory;
    private QueryBuilder $queryBuilder;
    private array $clientData;

    public function __construct(PasswordHashFactory $hashFactory, ConnectionPool $connectionPool)
    {
        $this->hashFactory = $hashFactory;
        $this->queryBuilder = $connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
    }

    /**
     * Get a client.
     *
     * @param string $clientIdentifier The client's identifier
     * @param string|null $grantType The grant type used (if sent)
     * @param string|null $clientSecret The client's secret (if sent)
     * @param bool $mustValidateSecret If true the client must attempt to validate the secret if the client
     *                                        is confidential
     *
     * @return ClientEntityInterface
     */
    public function getClientEntity($clientIdentifier, $grantType = null, $clientSecret = null, $mustValidateSecret = true)
    {
        // not all steps in OAuth2 supply a client secret, so we only validate it if it is supplied
        if ($mustValidateSecret && $clientSecret !== null && !$this->validateClient($clientIdentifier, $clientSecret, $grantType)) {
            $this->logger->debug(sprintf('Submitted secret "%s" is invalid for client %s', $clientSecret, $clientIdentifier));

            return null;
        }

        $clientData = $this->findRawByIdentifier($clientIdentifier);
        if (!$clientData) {
            return null;
        }

        return new Client(
            $clientIdentifier,
            $clientData['name'],
            GeneralUtility::trimExplode("\n", $clientData['redirect_uris']),
            true // todo: make configurable: $clientData['confidential']
        );
    }

    protected function findRawByIdentifier(string $clientIdentifier): ?array
    {
        return $this->queryBuilder
            ->resetQueryParts()
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where($this->queryBuilder->expr()->eq(
                'identifier',
                $this->queryBuilder->createNamedParameter($clientIdentifier)
            ))
            ->setMaxResults(1)
            ->execute()
            ->fetchAssociative() ?: null;
    }

    /**
     * Validate a client's secret.
     *
     * @param string $clientIdentifier The client's identifier
     * @param null|string $clientSecret The client's secret (if sent)
     * @param null|string $grantType The type of grant the client is using (if sent)
     *
     * @return bool
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        if ($clientSecret === null) {
            return false;
        }

        $clientData = $this->findRawByIdentifier($clientIdentifier);
        if (!$clientData) {
            return false;
        }

        $hash = $this->hashFactory->get($clientData['secret'], 'FE');
        return $hash->checkPassword($clientSecret, $clientData['secret']);
    }
}
