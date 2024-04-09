<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Service;

class IdentityHandlingFactory
{
    /**
     * @var IdentityHandlerInterface[]
     */
    private array $handlers = [];

    public function __construct(DefaultIdentityHandler $defaultIdentityHandler)
    {
        $this->handlers['_default'] = $defaultIdentityHandler;
    }

    public function addIdentityHandler(
        IdentityHandlerInterface $resourceHandler,
        string $clientId
    ): void {
        $this->handlers[$clientId] = $resourceHandler;
    }

    public function getIdentityHandler(string $clientId): IdentityHandlerInterface
    {
        return $this->handlers[$clientId] ?? $this->handlers['_default'];
    }
}
