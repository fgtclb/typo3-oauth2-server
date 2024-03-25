<?php

declare(strict_types=1);

namespace FGTCLB\TestOauth2Client\ResourceOwner;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class BasicResourceOwner implements ResourceOwnerInterface
{
    public function __construct(array $response)
    {

    }
    /**
     * @inheritDoc
     */
    public function getId()
    {
        // TODO: Implement getId() method.
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        // TODO: Implement toArray() method.
    }
}
