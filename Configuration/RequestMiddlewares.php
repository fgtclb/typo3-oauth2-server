<?php

declare(strict_types=1);

return [
    'frontend' => [
        'fgtclb/typo3-oauth-server/authorization' => [
            'target' => \FGTCLB\OAuth2Server\Middleware\OAuth2Authorization::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-frontend/static-route-resolver',
            ],
        ],
        // "typo3/cms-frontend/maintenance-mode" is the earliest non-internal middleware
        'fgtclb/typo3-oauth-server/token' => [
            'target' => \FGTCLB\OAuth2Server\Middleware\OAuth2AccessToken::class,
            'after' => [
                'typo3/cms-frontend/maintenance-mode',
            ],
            'before' => [
                'typo3/cms-frontend/static-route-resolver',
            ],
        ],
        'fgtclb/typo3-oauth-server/identity' => [
            'target' => \FGTCLB\OAuth2Server\Middleware\OAuth2Identity::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-frontend/static-route-resolver',
            ],
        ],
    ],
];
