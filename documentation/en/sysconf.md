# sysconf.php - The main application configuration file

## Definition

The *sysconf.php* file is the general configuration script for the application.

## Structure

This script sets a global variable named `SYSTEM` defined by `$GLOBALS['SYSTEM']`.

The SYSTEM variable is an array with the following structure of indexes:

- **'SYSTEM_NAME'** - A string with the name of the application;
- **'SYSTEM_VERSION'** - The application version. A string or an array with 3 indexes of the version: major, minor and release. Example: `[1, 0, 0]`
- **'PROJECT_CODE_NAME'** - A string with application code name.
- **'CHARSET'** - System charset. Example: 'UTF-8'
- **'TIMEZONE'** - System time zone.
- **'ACTIVE_ENVIRONMENT'** - A string with the active environment name. Example: 'development'. If empty the framework will define the environment using the host of the URI or the environment variable defined by 'ENVIRONMENT_VARIABLE'.
- **'ENVIRONMENT_VARIABLE'** - Defines the name of the environment variable to sets the application environment. Used when 'ACTIVE_ENVIRONMENT' is empty and the system fails to determines the environment by URI.
- **'CONSIDER_PORT_NUMBER'** - Defines that the port number must be used when environment is defined by URI.
- **'ENVIRONMENT_ALIAS'** - An array with a key pair where the key is a regular expression to search the host and the value is the environment.
- **'ROOT_PATH'** - The web server virtual host root path.
- **'PROJECT_PATH'** - The application root directory.
- **'SPRINGY_PATH'** - The framework library path.
- **'CONFIG_PATH'** - Configuration system path.
- **'APP_PATH'** - The application path.
- **'CONTROLER_PATH'** - Application controllers path.
- **'CLASS_PATH'** - Application classes path.
- **'VAR_PATH'** - The var path where the application will save temporary and cache files.
- **'MIGRATION_PATH'** - Path for the folder of the scripts with database struture changes.
- **'VENDOR_PATH'** - Path for the thirdy part components.
