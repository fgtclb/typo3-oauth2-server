<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 OAuth2 server',
    'description' => 'OAuth2 server implementation for TYPO3 frontend users',
    'category' => 'fe',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'info@fgtclb.com',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
            'frontend' => '11.5.0-11.5.99',
        ],
    ],
];
