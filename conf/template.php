<?php

/**
 * Springy Framework Configuration File.
 *
 * Do not remove this file.
 */

return [
    'template_engine' => 'smarty',
    'debug' => false,
    'auto_reload' => false,
    'strict_variables' => true,
    'autoescape' => false,
    'optimizations' => 1,
    'debugging_ctrl' => 'NONE',
    'default_template_path' => app_path() . DS . 'templates_default',
    'template_path' => app_path() . DS . 'templates',
    'template_config_path' => app_path() . DS . 'templates_conf',
    'compiled_template_path' => var_dir() . DS . 'compiled',
    'template_cached_path' => cache_dir(),
    'use_sub_dirs' => false,
    'errors' => [
        404 => '_error404',
        500 => '_error500',
        503 => '_error503',
    ],
];
