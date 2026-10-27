<?php

declare(strict_types=1);

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
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;

/**
 * Factory for OAuth2 authorization servers
 */
final class ServerFactory
{
    public function __construct(
        private readonly Configuration $configuration,
        private readonly ClientRepository $clientRepository,
        private readonly AccessTokenRepository $accessTokenRepository,
        private readonly ScopeRepository $scopeRepository,
        private readonly AuthorizationCodeRepository $authorizationCodeRepository,
        private readonly RefreshTokenRepository $refreshTokenRepository
    ) {}

    /**
     * Build an instance of an OAuth2 authorization server
     */
    public function buildAuthorizationServer(): AuthorizationServer
    {
        $server = new AuthorizationServer(
            $this->clientRepository,
            $this->accessTokenRepository,
            $this->scopeRepository,
            $this->configuration->getPrivateKeyFile(),
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
        );

        $authCodeGrant = new AuthCodeGrant(
            $this->authorizationCodeRepository,
            $this->refreshTokenRepository,
            $this->configuration->getAuthorizationCodeLifetime()
        );
        $authCodeGrant->setRefreshTokenTTL($this->configuration->getRefreshTokenLifetime());
        // Enable the authentication code grant on the server
        $server->enableGrantType($authCodeGrant, $this->configuration->getAccessTokenLifetime());

        $refreshTokenGrant = new RefreshTokenGrant($this->refreshTokenRepository);
        $refreshTokenGrant->setRefreshTokenTTL($this->configuration->getRefreshTokenLifetime());
        // Enable the refresh token grant on the server
        $server->enableGrantType($refreshTokenGrant, $this->configuration->getAccessTokenLifetime());

        return $server;
    }

    /**
     * Build an instance of an OAuth2 resource server
     */
    public function buildResourceServer(): ResourceServer
    {
        $validator = new BearerTokenValidator(
            $this->accessTokenRepository
        );
        return new ResourceServer(
            $this->accessTokenRepository,
            $this->configuration->getPublicKeyFile(),
            $validator
        );
    }
}
