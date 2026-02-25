<?php

/*
 * Springy Framework Configuration File.
 *
 * Used for "development" environment.
 *
 * If removed, only conf/db.php will be used.
 */

return [
    'cache' => [
        'type' => 'off',
        'server_addr' => '127.0.0.1',
        'server_port' => 11211,
    ],
    'default' => [
        'database_type' => 'mysql',
        'host_name' => '',
        'user_name' => '',
        'password' => '',
        'database' => '',
        'charset' => 'utf8',
        'persistent' => false,
    ],
];
