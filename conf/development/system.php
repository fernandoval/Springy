<?php

/*
 * Springy Framework Configuration File.
 *
 * Used for "development" environment.
 *
 * If removed, only conf/system.php will be used.
 */

return [
    'debug' => true,
    'maintenance' => false,
    'cache' => false,
    'cache-control' => 'no-cache',
    'session' => [
        'secure' => false,
    ],
    'system_error' => [
        'save_in_database' => false,
    ],
    'system_internal_methods' => [
        'about' => true,
        'phpinfo' => true,
        'system_errors' => true,
        'test_error' => true,
    ],
];
