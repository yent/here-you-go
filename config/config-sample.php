<?php

$conf['timezone'] = 'Europe/Paris';

$conf['logger'] = [
    'type' => 'server',
    'level' => \HereYouGo\Logger::INFO
];

$conf['db'] = [
    'dsn' => 'mysql:host=localhost;dbname=hereyougo',
    'user' => 'hereyougo',
    'passwd' => 'foobar',
];

//$conf['skin'] = 'custom_skin';

$conf['available_locales'] = ['en' => 'English'];

$conf['base_url'] = 'https://'.$_SERVER['SERVER_NAME'].'/';

$conf['lang'] = [
    'default' => 'en',
    'use_url' => true,
    'use_browser' => true,
    'use_user_pref' => true,
    'save_user_pref' => true,
];

$conf['auth'] = [
    'attributes' => ['id', 'emails', 'name'],
    'sp' => [
        'type' => false,
        // further sp config
    ],
    'remote' => [
        'enabled' => true,
        'users' => [ // TODO clarify this
            'foo@bar.tld' => [
                'specific allowed method:path for this user',
                'specific allowed method:paths for this user',
                'specific allowed method:paths for this user',
            ],
            '*' => ['common', 'allowed', 'method:paths', 'for all users', ''],
        ],
        'applications' => [ // TODO clarify this
            'application_id' => [
                'secret' => 'some_secret',
                'acl' => [
                    'allowed', 'method:paths'
                ]
            ]
        ],
    ],
];