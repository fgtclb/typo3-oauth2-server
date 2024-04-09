<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Service;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface IdentityHandlerInterface
{
    public function handleAuthenticatedRequest(ServerRequestInterface $request): ResponseInterface;
}
