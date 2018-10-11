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

/**
 * Factory for OAuth2 authorization servers
 */
final class ServerFactory
{
    public function buildAuthorizationServer(): AuthorizationServer
    {
        $clientRepository = new ClientRepository();
        $accessTokenRepository = new AccessTokenRepository();
        $scopeRepository = new ScopeRepository();
        $privateKey = __DIR__ . '/private.key';
        $encryptionKey = '1q9fIpNu0ljseePtMq03PkHOgJjSmL2rCsxLRDUE/ME=';
        $server = new AuthorizationServer(
            $clientRepository,
            $accessTokenRepository,
            $scopeRepository,
            $privateKey,
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

    public function buildResourceServer(): ResourceServer
    {
        $accessTokenRepository = new AccessTokenRepository();
        $publicKey = __DIR__ . '/public.key';
        $validator = new BearerTokenValidator(
            $accessTokenRepository
        );
        $server = new ResourceServer(
            $accessTokenRepository,
            $publicKey,
            $validator
        );

        return $server;
    }
}
