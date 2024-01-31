# sysconf.php - The main application configuration file

***WARNING!*** THIS FILE WAS DEPRECATED. THE FRAMEWORK YET LOAD IT IF EXISTS IN
4.6.x VERSION. BUT IN VERSION 4.7 OR GREATER THIS FILE WILL BE IGNORED.

## Definition

The *sysconf.php* file is the general configuration script for the application.

This script sets application configurations in `Kernel` class.

## Structure

The SYSTEM variable is an array with the following structure of indexes:

*   **'SYSTEM_NAME'** - A string with the name of the application;
*   **'SYSTEM_VERSION'** - The application version. A string or an array with 3
    indexes of the version: major, minor and release. Example: `[1, 0, 0]`
*   **'PROJECT_CODE_NAME'** - A string with application code name.
*   **'PROJECT_PATH'** - The application root directory.
*   **'CONFIG_PATH'** - Configuration system path.
*   **'APP_PATH'** - The application path.
*   **'VAR_PATH'** - The var path where the application will save temporary and
    cache files.
*   **'MIGRATION_PATH'** - Path for the folder of the scripts with database
    struture changes.
