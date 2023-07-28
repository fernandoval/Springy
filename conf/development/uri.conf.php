<?php

/*
 * Springy Framework Configuration File.
 *
 * Used for "development" environment.
 *
 * If removed, only uri.default.conf.php will be used.
 */
$conf = [
    'host_controller_path' => [
        'host.seusite.localhost' => ['diretorio'],
    ],
    'dynamic' => $_SERVER['HTTP_HOST'],
    'static'  => $_SERVER['HTTP_HOST'],
    'secure'  => $_SERVER['HTTP_HOST'],
];

// Configurações sobrescritas para hosts específicos (EXEMPLO)
$over_conf = [
    'host.seusite.localhost' => [
        'dynamic' => 'http://host.seusite.localhost',
    ],
];
