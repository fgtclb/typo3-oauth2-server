<?php declare(strict_types = 1);
$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 OAuth2 server',
    'description' => 'OAuth2 server implementation for TYPO3 frontend users',
    'category' => 'fe',
    'state' => 'alpha',
    'author' => 'FGTCLB',
    'author_email' => 'info@fgtclb.com',
    'version' => '1.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-11.5.99',
            'frontend' => '10.4.0-11.5.99',
        ],
    ],
];
