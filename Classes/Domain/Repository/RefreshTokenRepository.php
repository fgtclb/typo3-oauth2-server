<?php
declare(strict_types = 1);

namespace FGTCLB\OAuth2Server\Domain\Repository;

use FGTCLB\OAuth2Server\Domain\Entity\RefreshToken;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

/**
 * Repository for OAuth2 refresh tokens
 */
final class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getNewRefreshToken()
    {
        return new RefreshToken();
    }

    /**
     * @inheritDoc
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
        // TODO: Persist token to the datbase or similar for audit logging or revocation
    }

    /**
     * Revoke the refresh token.
     *
     * @param string $tokenId
     */
    public function revokeRefreshToken($tokenId)
    {
        // TODO: Revoke persisted token
    }

    /**
     * @inheritDoc
     */
    public function isRefreshTokenRevoked($tokenId)
    {
        // TODO: Check if persisted token is revoked
        return false;
    }
}
