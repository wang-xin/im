<?php

require './src/WebSocket.php';

$config = [
    'server' => '160.19.51.200',
    'port'   => 9501,
];

$server = new WebSocket($config);

$server->authenticator = function (){
    return true;
};

$server->run();