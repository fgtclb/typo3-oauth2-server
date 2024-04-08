<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Service;

class ResourceHandlingFactory
{
    /**
     * @var ResourceHandlerInterface[]
     */
    private array $handlers = [];

    public function __construct(DefaultResourceHandler $defaultResourceHandler)
    {
        $this->handlers['_default'] = $defaultResourceHandler;
    }

    public function addResourceHandler(
        ResourceHandlerInterface $resourceHandler,
        string $clientId
    ): void {
        $this->handlers[$clientId] = $resourceHandler;
    }

    public function getResourceHandler(string $clientId): ResourceHandlerInterface
    {
        return $this->handlers[$clientId] ?? $this->handlers['_default'];
    }
}
