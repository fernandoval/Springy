<?php

/*
 * General system configutations.
 */

return [
    'debug' => true,
    'ignore_deprecated' => false,
    'rewrite_url' => true,
    'cache-control' => 'private, must-revalidate',
    'authentication' => [],
    'developer_user' => '',
    'developer_pass' => '',
    'dba_user' => '',
    'bug_authentication' => [],
    'assets_source_path' => app_path() . DS . 'assets',
    'assets_path' => web_root() . DS . 'assets',

    /*
     * Session configuration
     */
    'session' => [
        /*
         * Defines the session engine type.
         *
         * Supported values:
         *
         * 'file'      : default PHP session controll;
         * 'memcached' : uses MemcacheD service;
         * 'database'  : uses database table.
         */
        'type' => 'file',

        /*
         * The session cookie name.
         */
        'name' => 'SPRINGYSID',

        /*
         * Session cookie domain.
         */
        'domain' => '',

        /*
         * Session expiration time in seconds.
         */
        'expires' => 120,

        /*
         * Uses secure cookie for sessions.
         */
        'secure' => true,

        /*
         * MemcacheD service configurations.
         * Used only if 'memcached' defined as session engine type.
         */
        'memcached' => [
            'address' => '127.0.0.1',
            'port' => 11211,
        ],

        /*
         * Database configurations.
         * Used only if 'database' defined as session engine type.
         */
        'database' => [
            'server' => 'default',
            'table' => '_sessions',
        ],
    ],

    /*
     * "Remember me" token configuration.
     *
     * The storage driver is chosen in the 'security.remember.storage' binding
     * (see app/helpers.php). Only the section of the chosen driver is used.
     */
    'remember_me' => [
        /*
         * Token and cookie lifetime in seconds. Default: 60 days.
         */
        'lifetime' => 5184000,

        /*
         * Redis or Valkey service configurations.
         */
        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int) env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', ''),
            'database' => (int) env('REDIS_DATABASE', 0),
            'timeout' => 2.0,
            'prefix' => 'springy:remember:',
        ],

        /*
         * MemcacheD service configurations.
         */
        'memcached' => [
            'address' => env('MEMCACHED_HOST', '127.0.0.1'),
            'port' => (int) env('MEMCACHED_PORT', 11211),
            'prefix' => 'springy.remember.',
        ],

        /*
         * Database configurations.
         * Table structure: springy/Security/Remember/remember_tokens_create_table.sql
         */
        'database' => [
            // Connection name from conf/database.php. Null uses the default connection.
            'connection' => null,
            'table' => '_remember_tokens',
        ],
    ],

    /*
     * System error configurations.
     */
    'system_error' => [
        /*
         * HTTP response code that will be reported as system errors.
         */
        'reported_errors' => [
            405,
            406,
            408,
            409,
            410,
            412,
            413,
            414,
            415,
            416,
            417,
            418,
            422,
            423,
            424,
            425,
            426,
            450,
            499,
            500,
            501,
            502,
            504,
            505,
        ],

        /*
         * Save system erros in database errors table.
         */
        'save_in_database' => false,

        /*
         * Database error table name. Only if errors saved in database.
         */
        'table_name' => '_system_errors',

        /*
         * Database connection name for errors saved in database.
         */
        'db_server' => 'default',

        /*
         * Sets error table creation if does not exists.
         * The system will use system_errors_create_table.sql script to it.
         */
        'create_table' => true,
    ],

    'system_internal_methods' => [
        'about' => false,
        'phpinfo' => false,
        'system_errors' => false,
        'test_error' => false,
    ],
];
