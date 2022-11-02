<?php
declare(strict_types = 1);

namespace FGTCLB\OAuth2Server\Server;

use FGTCLB\OAuth2Server\Configuration;
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
    protected Configuration $configuration;

    /**
     * @param Configuration|null $configuration
     */
    public function __construct(Configuration $configuration = null)
    {
        $this->configuration = $configuration ?: GeneralUtility::makeInstance(Configuration::class);
    }

    /**
     * Build an instance of an OAuth2 authorization server
     *
     * @return AuthorizationServer
     */
    public function buildAuthorizationServer(): AuthorizationServer
    {
        $clientRepository = GeneralUtility::makeInstance(ClientRepository::class);
        $accessTokenRepository = new AccessTokenRepository();
        $scopeRepository = new ScopeRepository();
        $server = new AuthorizationServer(
            $clientRepository,
            $accessTokenRepository,
            $scopeRepository,
            $this->configuration->getPrivateKeyFile(),
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
        );

        $authorizationCodeRepository = new AuthorizationCodeRepository();
        $refreshTokenRepository = new RefreshTokenRepository();
        $grant = new AuthCodeGrant(
            $authorizationCodeRepository,
            $refreshTokenRepository,
            $this->configuration->getAuthorizationCodeLifetime()
        );
        $grant->setRefreshTokenTTL($this->configuration->getRefreshTokenLifetime());
        // Enable the authentication code grant on the server
        $server->enableGrantType($grant, $this->configuration->getAccessTokenLifetime());

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
        return new ResourceServer(
            $accessTokenRepository,
            $this->configuration->getPublicKeyFile(),
            $validator
        );
    }
}
