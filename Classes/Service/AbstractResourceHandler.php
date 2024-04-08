<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Service;

use FGTCLB\OAuth2Server\Configuration;

abstract class AbstractResourceHandler implements ResourceHandlerInterface
{
    protected string $clientId = '';
    /**
     * @var string[]
     */
    protected array $scopes = [];
    protected Configuration $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }
}
