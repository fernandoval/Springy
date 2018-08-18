<?php
/**
 * Springy Framework general configuration file.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   3.4.1.20
 */
$GLOBALS['SYSTEM'] = [
    'SYSTEM_NAME'       => 'Your system name',
    'SYSTEM_VERSION'    => [1, 0, 0],
    'PROJECT_CODE_NAME' => '',
    'CHARSET'           => 'UTF-8',

    'ACTIVE_ENVIRONMENT'   => '',
    'ENVIRONMENT_VARIABLE' => 'SPRINGY_ENVIRONMENT',
    'CONSIDER_PORT_NUMBER' => false,
    'ENVIRONMENT_ALIAS'    => [
        'localhost'             => 'development',
        '127\.0\.0\.1'          => 'development',
        '(www\.)?mydomain\.com' => 'production',
    ],

    // Web server doc root directory
    'ROOT_PATH'      => realpath(dirname(__FILE__)),
    // Project root directory
    'PROJECT_PATH'   => realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'),
    // Springy library directory
    'SPRINGY_PATH'   => realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'springy'),
    // Configuration directory
    'CONFIG_PATH'    => realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'conf'),
    // Application directory
    'APP_PATH'       => realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'app'),
    'CONTROLER_PATH' => '',
    'CLASS_PATH'     => '',
    // Directory where the system writes data during the course of its operation
    'VAR_PATH'       => realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'var'),
    // Directory for the subdirectories with migration scripts
    'MIGRATION_PATH' => realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'migration'),
    /// Vendor directory
    'VENDOR_PATH'    => realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'),

    'TIMEZONE' => 'America/Sao_Paulo',
];

/// Diretório das controladoras
$GLOBALS['SYSTEM']['CONTROLER_PATH'] = realpath($GLOBALS['SYSTEM']['APP_PATH'].DIRECTORY_SEPARATOR.'controllers');
/// Diretório das classes da aplicação
$GLOBALS['SYSTEM']['CLASS_PATH'] = realpath($GLOBALS['SYSTEM']['APP_PATH'].DIRECTORY_SEPARATOR.'classes');
