<?php

declare(strict_types=1);

namespace FGTCLB\Testsuite\Service;

use FGTCLB\OAuth2Server\Service\AbstractResourceHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\JsonResponse;

class AcmeClientResourceHandler extends AbstractResourceHandler
{
    public function handleAuthenticatedRequest(ServerRequestInterface $request): ResponseInterface
    {
        $userId = (int)$request->getAttribute('oauth_user_id');
        $loggedInUser = BackendUtility::getRecord(
            'fe_users',
            $userId,
            'uid,username',
        ) ?: ['uid' => 0, 'username' => ''];

        return new JsonResponse(
            [
                'user_id' => (int)$loggedInUser['uid'],
                'username' => $loggedInUser['username'],
            ]
        );
    }
}
