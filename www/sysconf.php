<?php

/*
 * Springy Framework general configuration file.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   3.5.0
 */

$baseDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');

return [
    'SYSTEM_NAME' => 'Your system name',
    'SYSTEM_VERSION' => [1, 0, 0],
    'PROJECT_CODE_NAME' => '',
    'CHARSET' => 'UTF-8',
    'TIMEZONE' => 'America/Sao_Paulo',

    'ACTIVE_ENVIRONMENT' => '',
    'ENVIRONMENT_VARIABLE' => 'SPRINGY_ENVIRONMENT',
    'CONSIDER_PORT_NUMBER' => false,
    'ENVIRONMENT_ALIAS' => [
        'localhost' => 'development',
        '127\.0\.0\.1' => 'development',
        '(www\.)?mydomain\.com' => 'production',
    ],

    // Web server doc root directory
    'ROOT_PATH' => __DIR__,
    // Project root directory
    'PROJECT_PATH' => $baseDir,
    // Springy library directory
    'SPRINGY_PATH' => $baseDir . DIRECTORY_SEPARATOR . 'springy',
    // Configuration directory
    'CONFIG_PATH' => $baseDir . DIRECTORY_SEPARATOR . 'conf',
    // Application directory
    'APP_PATH' => $baseDir . DIRECTORY_SEPARATOR . 'app',
    // Controller path
    // @deprecated 4.5
    'CONTROLER_PATH' => $baseDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'controllers',
    // Models and other classes path
    // @deprecated 4.5
    'CLASS_PATH' => $baseDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'classes',
    // Directory where the system writes data during the course of its operation
    'VAR_PATH' => $baseDir . DIRECTORY_SEPARATOR . 'var',
    // Directory for the subdirectories with migration scripts
    'MIGRATION_PATH' => $baseDir . DIRECTORY_SEPARATOR . 'migration',
    // Vendor directory
    'VENDOR_PATH' => $baseDir . DIRECTORY_SEPARATOR . 'vendor',
];
