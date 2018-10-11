<?php
declare(strict_types = 1);

namespace FGTCLB\OAuth2Server\Domain\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

/**
 * OAuth2 client
 */
final class Client implements ClientEntityInterface
{
    use ClientTrait;
    use EntityTrait;
}
