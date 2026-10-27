<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Domain\Repository;

use FGTCLB\OAuth2Server\Domain\Entity\RefreshToken;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

/**
 * Repository for OAuth2 refresh tokens
 */
final class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new RefreshToken();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        // TODO: Persist token to the datbase or similar for audit logging or revocation
    }

    /**
     * Revoke the refresh token.
     */
    public function revokeRefreshToken(string $tokenId): void
    {
        // TODO: Revoke persisted token
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        // TODO: Check if persisted token is revoked
        return false;
    }
}
