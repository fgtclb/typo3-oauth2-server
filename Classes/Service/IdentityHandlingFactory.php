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

    public function __construct(
        private readonly DefaultIdentityHandler $defaultIdentityHandler,
        private readonly Configuration $configuration
    ) {
        $defaultIdentityHandler->setConfiguration($configuration);
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
