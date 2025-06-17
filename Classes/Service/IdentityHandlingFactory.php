<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Service;

use FGTCLB\OAuth2Server\Configuration;

final class IdentityHandlingFactory
{
    /**
     * @var IdentityHandlerInterface[]
     */
    private array $handlers = [];

    private Configuration $configuration;
    private DefaultIdentityHandler $defaultIdentityHandler;

    public function __construct(
        DefaultIdentityHandler $defaultIdentityHandler,
        Configuration $configuration
    ) {
        $defaultIdentityHandler->setConfiguration($configuration);
        $this->configuration = $configuration;
        $this->defaultIdentityHandler = $defaultIdentityHandler;
    }

    public function addIdentityHandler(
        IdentityHandlerInterface $resourceHandler,
        string $clientId
    ): void {
        $resourceHandler->setConfiguration($this->configuration);
        $this->handlers[$clientId] = $resourceHandler;
    }

    public function getIdentityHandler(string $clientId): IdentityHandlerInterface
    {
        return $this->handlers[$clientId] ?? $this->defaultIdentityHandler->setClientId($clientId);
    }
}
