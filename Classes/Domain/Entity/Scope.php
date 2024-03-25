<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Domain\Entity;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

/**
 * OAuth2 scope
 */
final class Scope implements ScopeEntityInterface
{
    use EntityTrait;

    public function jsonSerialize(): mixed
    {
        return $this->identifier;
    }
}
