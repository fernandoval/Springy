<?php

/*
 * Springy Framework general configuration file.
 *
 * WARNING! This configuration file is deprecated but is loaded yet in version
 * 4.6.x if exists. Will be removed and ignored in version 4.7 or greater.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @deprecated 4.6.0
 *
 * @see .env.example and const files at the project root.
 *
 * @version 3.6.0
 */

$baseDir = dirname(__DIR__);

return [
    'SYSTEM_NAME' => 'Your system name',
    'SYSTEM_VERSION' => [1, 0, 0],
    'PROJECT_CODE_NAME' => '',

    // Project root directory
    'PROJECT_PATH' => $baseDir,
    // Configuration directory
    'CONFIG_PATH' => $baseDir . DIRECTORY_SEPARATOR . 'conf',
    // Application directory
    'APP_PATH' => $baseDir . DIRECTORY_SEPARATOR . 'app',
    // Directory where the system writes data during the course of its operation
    'VAR_PATH' => $baseDir . DIRECTORY_SEPARATOR . 'var',
    // Directory for the subdirectories with migration scripts
    'MIGRATION_PATH' => $baseDir . DIRECTORY_SEPARATOR . 'migration',
];
