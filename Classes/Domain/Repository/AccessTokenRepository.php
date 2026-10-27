<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Domain\Repository;

use FGTCLB\OAuth2Server\Domain\Entity\AccessToken;
use FGTCLB\OAuth2Server\Exception\IdentifierEmptyException;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

/**
 * Repository for OAuth2 access tokens
 */
final class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * @throws IdentifierEmptyException
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, ?string $userIdentifier = null): AccessTokenEntityInterface
    {
        if (empty($userIdentifier)) {
            throw new IdentifierEmptyException(
                'The user identifier was null or empty.',
                1793110512
            );
        }
        $accessToken = new AccessToken();
        $accessToken->setClient($clientEntity);
        $accessToken->setUserIdentifier($userIdentifier);

        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }

        return $accessToken;
    }

    /**
     * Persists a new access token to permanent storage.
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        // TODO: Persist token to the datbase or similar for audit logging or revocation
    }

    /**
     * Revoke an access token.
     */
    public function revokeAccessToken(string $tokenId): void
    {
        // TODO: Revoke persisted token
    }

    /**
     * Check if the access token has been revoked.
     */
    public function isAccessTokenRevoked(string $tokenId): bool
    {
        // TODO: Check if persisted token is revoked
        return false;
    }
}
