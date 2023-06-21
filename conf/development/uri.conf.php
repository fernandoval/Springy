<?php

/**
 * Springy Framework Configuration File.
 *
 * Used for "development" environment.
 *
 * If removed, only uri.default.conf.php will be used.
 */

/**
 *  \defgroup uricfg_development Configurações da classe de tratamento de URI/URL para o ambiente 'development'
 *  \ingroup uricfg.
 *
 *  As entradas colocadas nesse arquivo serão aplicadas apenas ao ambiente 'development'.
 *
 *  Seu sistema pode não possuir esse ambiente, então use-o como modelo para criação do arquivo de
 *  parâmetros de configuração para os ambientes que seu sistema possua.
 *
 *  Veja \link uricfg Configurações da classe de tratamento de URI/URL \endlink para entender as entradas de configuração possíveis.
 *
 *  \see uricfg
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
        'dynamic'                => 'http://host.seusite.localhost',
        'prevalidate_controller' => [
            'mycontroller' => ['command' => 404, 'segments' => 2, 'validate' => ['/^[a-z0-9\-]+$/', '/^[0-9]+$/']],
        ],
    ],
];
