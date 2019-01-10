<?php
declare(strict_types = 1);

namespace FGTCLB\OAuth2Server\Domain\Entity;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\UserEntityInterface;

/**
 * OAuth2 user
 */
final class User implements UserEntityInterface
{
    use EntityTrait;

    /**
     * @param int $uid User UID
     */
    public function __construct(int $uid)
    {
        $this->identifier = $uid;
    }
}
