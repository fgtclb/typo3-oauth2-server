<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Domain\Repository;

use FGTCLB\OAuth2Server\Domain\Entity\AuthorizationCode;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

/**
 * Repository for OAuth2 authorization codes
 */
final class AuthorizationCodeRepository implements AuthCodeRepositoryInterface
{
    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new AuthorizationCode();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        // TODO: Persist code to the datbase or similar for audit logging or revocation
    }

    public function revokeAuthCode(string $codeId): void
    {
        // TODO: Revoke persisted code
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        // TODO: Check if persisted code is revoked
        return false;
    }
}
