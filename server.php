<?php

require './src/WebSocket.php';

$config = [
    'server' => '0.0.0.0',
    'port'   => 9501,
];

$server = new WebSocket($config);

$server->authenticator = function (){
    return true;
};

$server->run();