<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        'db' => [
            'host' => '103.253.212.172',
            'user' => 'yogaa209_okkpd',
            'pass' => 'Okkpd2018!',
            'dbname' => 'yogaa209_okkpd',
            'driver' => 'mysql'
        ],
        'ftp' => [
            'host' => 'yogaadi.xyz',
            'user' => 'okkpd_upload_api@yogaadi.xyz',
            'pass' => 'OkkpdApi2019!',
            'temp_loc' => '../public/upload/'
        ]
    ],
];
