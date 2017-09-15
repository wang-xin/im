<?php

require './src/WebSocket.php';

$config = [
    'ws'    => [
        'host' => 'im.xin-ge.cc',
        'port' => 9501,
    ],
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
    ],
];

$server = new WebSocket($config);
$redis  = new Redis();
$redis->connect($config['redis']['host'], $config['redis']['port']);

$server->authenticator = function ($request) use ($redis) {
    $accessToken = $request->get['access-token'];

    if (!$accessToken || !$userInfo = $redis->get($accessToken)) {
        return false;
    }

    // $userInfo = [
    //     'id'   => '1',
    //     'name' => 'King',
    //     'avatar' => './images/avatar/1.jpg',
    // ];
    return unserialize($userInfo);
};

$server->run();
