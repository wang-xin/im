<?php

require './src/WebSocket.php';

$config = [
    'ws' => [
        'host' => '0.0.0.0',
        'port'   => 9501,
    ],
    'redis' => [
        'host' => '127.0.0.1',
        'port'   => 6379,
    ]
];

$server = new WebSocket($config);

$server->authenticator = function ($request){
    $userInfo = [
        'name' => 'King'
    ];
    // return $userInfo;

    return false;
};

$server->run();