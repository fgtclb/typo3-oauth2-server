<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Service;

use FGTCLB\OAuth2Server\Configuration;

abstract class AbstractIdentityHandler implements IdentityHandlerInterface
{
    /**
     * @var string[]
     */
    protected array $scopes = [];

    protected ?Configuration $configuration = null;

    /**
     * Ensure that configuration can be passed and made available
     * in all handler implementation as enforcing constructor in
     * interfaces are discouraged. Same counts eventually provided
     * abstract implementations to leave constructor conflict free.
     *
     * {@see IdentityHandlingFactory::addIdentityHandler()}
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
    }
}
