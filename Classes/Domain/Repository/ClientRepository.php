<?php
declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Domain\Repository;

use FGTCLB\OAuth2Server\Domain\Entity\Client;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for OAuth2 clients
 */
final class ClientRepository implements ClientRepositoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const TABLE_NAME = 'tx_oauth2server_client';

    /**
     * @var PasswordHashFactory
     */
    private $hashFactory;

    /**
     * @var \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    private $queryBuilder;

    public function __construct()
    {
        $this->hashFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(static::TABLE_NAME);
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
        $clientData = $this->findRawByIdentifier($clientIdentifier, ['*']);
        if (!$clientData) {
            return null;
        }

        // not all steps in OAuth2 supply a client secret, so we only validate it if it is supplied
        if ($mustValidateSecret && $clientSecret !== null) {
            if (!$this->validateClient($clientData, (string)$clientSecret)) {
                $this->logger->debug(sprintf('Submitted secret "%s" is invalid for client %s', $clientIdentifier, (string)$clientSecret));

                return null;
            }
        }

        return new Client(
            $clientIdentifier,
            $clientData['name'],
            GeneralUtility::trimExplode("\n", $clientData['redirect_uris'])
        );
    }

    protected function findRawByIdentifier(string $clientIdentifier, $selects = ['*']): ?array
    {
        return $this->queryBuilder
            ->resetQueryParts()
            ->select(...$selects)
            ->from(static::TABLE_NAME)
            ->where($this->queryBuilder->expr()->eq(
                'identifier',
                $this->queryBuilder->createNamedParameter($clientIdentifier)
            ))
            ->setMaxResults(1)
            ->execute()
            ->fetch() ?: null;
    }

    private function validateClient(array $clientData, string $submittedSecret)
    {
        $hash = $this->hashFactory->get($clientData['secret'], 'FE');
        return $hash->checkPassword($submittedSecret, $clientData['secret']);
    }
}
