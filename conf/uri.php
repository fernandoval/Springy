<?php

/*
 * Springy Framework Configuration File.
 *
 * As entradas colocadas nesse arquivo serão aplicadas a todos os ambientes do sistema.
 *
 * Do not remove this file.
 */
$conf = [
    /*
     * New routing for PSR-4 controllers
     */
    'routing' => [
        /*
         * Default namespace for controllers.
         *
         * @var string
         */
        'namespace' => 'App\\Web\\',

        /*
         * Default namespaces by URI segments.
         *
         * @var array
         */
        'segments' => [
            'api' => 'App\\Api',
        ],

        /*
         * Routing configuration by HTTP host.
         *
         * Keys are regular expressions.
         *
         * @var array
         */
        'hosts' => [
            'localhost\.localdomain' => [
                'module' => 'local',
                'namespace' => 'App\\Local\\Web',
                'segments' => [
                    'api' => 'App\\Local\\Api',
                ],
                'template' => ['$admin'],
            ],
            // Command line controllers
            'cmd\.shell' => [
                'module' => '',
                'namespace' => 'App\\Console',
                'segments' => [],
                'template' => [],
            ],
        ],

        /*
         * Page routing.
         *
         * @var array
         */
        'routes' => [
            'App\\Web\\' => [
                'end-of-user-license-agreement' => 'Eula',
            ],
        ],
    ],

    'system_root' => '/',
    // URLs comuns do site
    'common_urls' => [
        'urlAssets' => [['assets'], [], false, 'static', true],
        'urlHome' => [[]],
        'urlLogin' => [['login'], [], false, 'secure', true],
        'urlLogout' => [['logout'], [], false, 'secure', true],
    ],
    'ignored_segments' => 0,
    'assets_dir' => 'assets',

    /*
     * Application hosts
     *
     * This configuration are used by url() function to create an URL.
     *
     * @var array
     */
    'hosts' => [],
];
