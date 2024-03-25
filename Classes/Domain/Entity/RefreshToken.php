<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Domain\Entity;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;

/**
 * OAuth2 refresh token
 */
final class RefreshToken implements RefreshTokenEntityInterface
{
    use RefreshTokenTrait;
    use EntityTrait;
}
