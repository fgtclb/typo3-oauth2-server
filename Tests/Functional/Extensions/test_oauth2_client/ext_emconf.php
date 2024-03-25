<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 OAuth2 Test Client',
    'description' => 'OAuth2 test client extension',
    'category' => 'fe',
    'state' => 'alpha',
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
