<?php

/**
 * Springy Framework Configuration File.
 *
 * Used for "production" environment.
 *
 * If removed, only uri.default.conf.php will be used.
 */

/**
 *  \defgroup uricfg_production Configurações da classe de tratamento de URI/URL para o ambiente 'production'
 *  \ingroup uricfg.
 *
 *  As entradas colocadas nesse arquivo serão aplicadas apenas ao ambiente 'production'.
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
        'host.seusite.com' => ['diretorio'],
    ],
    'dynamic' => $_SERVER['HTTP_HOST'],
    'static'  => $_SERVER['HTTP_HOST'],
    'secure'  => $_SERVER['HTTP_HOST'],
];
