<?php
declare(strict_types = 1);

namespace FGTCLB\OAuth2Server\Domain\Repository;

use FGTCLB\OAuth2Server\Domain\Entity\AuthorizationCode;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

/**
 * Repository for OAuth2 authorization codes
 */
final class AuthorizationCodeRepository implements AuthCodeRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getNewAuthCode()
    {
        return new AuthorizationCode();
    }

    /**
     * @inheritDoc
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        // TODO: Persist code to the datbase or similar for audit logging or revocation
    }

    /**
     * @inheritDoc
     */
    public function revokeAuthCode($codeId)
    {
        // TODO: Revoke persisted code
    }

    /**
     * @inheritDoc
     */
    public function isAuthCodeRevoked($codeId)
    {
        // TODO: Check if persisted code is revoked
        return false;
    }
}
