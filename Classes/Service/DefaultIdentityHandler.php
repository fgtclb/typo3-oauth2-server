<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Service;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

final class DefaultIdentityHandler extends AbstractIdentityHandler
{
    private string $clientId = '';

    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function handleAuthenticatedRequest(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse(
            [
                'error' => sprintf(
                    'Client ID "%s" has no implemented Handler in OAuth Response server',
                    $this->clientId
                ),
            ],
            501
        );
    }
}
