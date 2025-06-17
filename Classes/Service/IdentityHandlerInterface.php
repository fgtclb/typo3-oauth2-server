<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Service;

use FGTCLB\OAuth2Server\Configuration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface IdentityHandlerInterface
{
    /**
     * Ensure that configuration can be passed and made available
     * in all handler implementation as enforcing constructor in
     * interfaces are discouraged. Same counts eventually provided
     * abstract implementations to leave constructor conflict free.
     *
     * {@see IdentityHandlingFactory::addIdentityHandler()}
     */
    public function setConfiguration(Configuration $configuration): void;

    /**
     * Handles the identity request. Only called if route path matches `/oauth2/identity`
     * and client id matches the value used for registering the implemented handler.
     */
    public function handleAuthenticatedRequest(ServerRequestInterface $request): ResponseInterface;
}
