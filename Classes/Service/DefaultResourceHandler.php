<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Service;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

class DefaultResourceHandler extends AbstractResourceHandler
{
    public function handleAuthenticatedRequest(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse(
            ['error' => 'Client ID has no implemented Handler in OAuth Response server'],
            501
        );
    }
}
