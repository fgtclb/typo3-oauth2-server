<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Domain\Entity;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AuthCodeTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

/**
 * OAuth2 authorization code
 */
final class AuthorizationCode implements AuthCodeEntityInterface
{
    use EntityTrait;
    use AuthCodeTrait;
    use TokenEntityTrait;
}
