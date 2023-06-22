<?php

/**
 * Springy Framework Configuration File.
 *
 * As entradas colocadas nesse arquivo serão aplicadas a todos os ambientes do sistema.
 *
 * Do not remove this file.
 */
$conf = [
    'template_engine' => 'smarty',
    'debug' => false,
    'auto_reload' => false,
    'strict_variables' => true,
    'autoescape' => false,
    'optimizations' => 1,
    'debugging_ctrl' => 'NONE',
    'default_template_path' => sysconf('APP_PATH') . DS . 'templates_default',
    'template_path' => sysconf('APP_PATH') . DS . 'templates',
    'template_config_path' => sysconf('APP_PATH') . DS . 'templates_conf',
    'compiled_template_path' => sysconf('VAR_PATH') . DS . 'compiled',
    'template_cached_path' => sysconf('VAR_PATH') . DS . 'cache',
    'use_sub_dirs' => false,
    'errors' => [
        404 => '_error404',
        500 => '_error500',
        503 => '_error503',
    ],
];
