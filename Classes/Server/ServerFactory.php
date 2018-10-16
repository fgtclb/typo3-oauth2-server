<?php
declare(strict_types = 1);

namespace FGTCLB\OAuth2Server\Server;

use FGTCLB\OAuth2Server\Domain\Repository\AccessTokenRepository;
use FGTCLB\OAuth2Server\Domain\Repository\AuthorizationCodeRepository;
use FGTCLB\OAuth2Server\Domain\Repository\ClientRepository;
use FGTCLB\OAuth2Server\Domain\Repository\RefreshTokenRepository;
use FGTCLB\OAuth2Server\Domain\Repository\ScopeRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\AuthorizationValidators\BearerTokenValidator;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\ResourceServer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory for OAuth2 authorization servers
 */
final class ServerFactory
{
    /**
     * @var ServerConfiguration
     */
    protected $configuration;

    /**
     * @param ServerConfiguration|null $configuration
     */
    public function __construct(ServerConfiguration $configuration = null)
    {
        $this->configuration = $configuration ?: GeneralUtility::makeInstance(ServerConfiguration::class);
    }

    /**
     * Build an instance of an OAuth2 authorization server
     *
     * @return AuthorizationServer
     */
    public function buildAuthorizationServer(): AuthorizationServer
    {
        $clientRepository = new ClientRepository();
        $accessTokenRepository = new AccessTokenRepository();
        $scopeRepository = new ScopeRepository();
        $encryptionKey = '1q9fIpNu0ljseePtMq03PkHOgJjSmL2rCsxLRDUE/ME=';
        $server = new AuthorizationServer(
            $clientRepository,
            $accessTokenRepository,
            $scopeRepository,
            $this->configuration->getPrivateKeyFile(),
            $encryptionKey
        );

        $authorizationCodeRepository = new AuthorizationCodeRepository();
        $refreshTokenRepository = new RefreshTokenRepository();
        $grant = new AuthCodeGrant(
            $authorizationCodeRepository,
            $refreshTokenRepository,
            new \DateInterval('PT10M') // authorization codes will expire after 10 minutes
        );
        $grant->setRefreshTokenTTL(new \DateInterval('P1M')); // refresh tokens will expire after 1 month
        // Enable the authentication code grant on the server
        $server->enableGrantType(
            $grant,
            new \DateInterval('PT1H') // access tokens will expire after 1 hour
        );

        return $server;
    }

    /**
     * Build an instance of an OAuth2 resource server
     *
     * @return ResourceServer
     */
    public function buildResourceServer(): ResourceServer
    {
        $accessTokenRepository = new AccessTokenRepository();
        $validator = new BearerTokenValidator(
            $accessTokenRepository
        );
        $server = new ResourceServer(
            $accessTokenRepository,
            $this->configuration->getPublicKeyFile(),
            $validator
        );

        return $server;
    }
}
