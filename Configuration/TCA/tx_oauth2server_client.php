<?php

return [
    'ctrl' => [
        'label' => 'name',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'title' => 'LLL:EXT:oauth2_server/Resources/Private/Language/locallang_client_tca.xlf:client.title',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'adminOnly' => true,
        'rootLevel' => 1,
        'default_sortby' => 'name',
        'enablecolumns' => [
            'disabled' => 'disable',
            'starttime' => 'starttime',
            'endtime' => 'endtime'
        ],
        'searchFields' => 'name'
    ],
    'interface' => [
        'showRecordFieldList' => 'name,identifier,redirect_uris,description'
    ],
    'columns' => [
        'name' => [
            'label' => 'LLL:EXT:oauth2_server/Resources/Private/Language/locallang_client_tca.xlf:client.name.label',
            'config' => [
                'type' => 'input',
                'size' => 32,
                'max' => 255,
                'eval' => 'trim,required',
            ]
        ],
        'identifier' => [
            'label' => 'LLL:EXT:oauth2_server/Resources/Private/Language/locallang_client_tca.xlf:client.identifier.label',
            'config' => [
                'type' => 'input',
                'size' => 32,
                'max' => 32,
                'eval' => 'trim,unique,required'
            ]
        ],
        'secret' => [
            'label' => 'LLL:EXT:oauth2_server/Resources/Private/Language/locallang_client_tca.xlf:client.secret.label',
            'config' => [
                'type' => 'input',
                'size' => 32,
                'max' => 100,
                'eval' => 'trim,required,password,saltedPassword',
                'autocomplete' => false,
            ]
        ],
        'redirect_uris' => [
            'label' => 'LLL:EXT:oauth2_server/Resources/Private/Language/locallang_client_tca.xlf:client.redirect_uris.label',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
                'max' => 2000,
            ]
        ],
    ],
    'types' => [
        '0' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;name, --palette--;;credentials, redirect_uris
            
        ']
    ],
    'palettes' => [
        'name' => ['showitem' => 'name, --linebreak--, description'],
        'credentials' => ['showitem' => 'identifier, --linebreak--, secret'],
    ],
];
