<?php

/*
 * Springy Framework Configuration File.
 *
 * Used for "production" environment.
 *
 * If removed, only db.default.conf.php will be used.
 */
$conf = [
    'round_robin' => [
        'type'        => 'memcached',
        'server_addr' => 'youmemcachedserver.localnetwork',
        'server_port' => 11211,
    ],
    'cache' => [
        'type'        => 'off',
        'server_addr' => 'youmemcachedserver.localnetwork',
        'server_port' => 11211,
    ],
    'default' => [
        'database_type' => 'mysql',
        'host_name'     => '',
        'user_name'     => '',
        'password'      => '',
        'database'      => '',
        'charset'       => 'utf8',
        'persistent'    => false,
    ],
];
