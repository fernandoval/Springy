# System Configuration File (system)

The files **system.default.conf.php** in /conf directory and **system.conf.php** in environment subdirectories is used by **Kernel** and other core libraries.

Like other configuration files of the framework you can add configuration entries and change pre-defined sets, but can not delete it.

## Pre-defined configurations

- **'debug'** - (boolean) Turns on/off the system debug.
- **'ignore_deprecated'** - (boolean) Sets the error handler system to ignores or catches the deprecated funcion warnings.
- **'rewrite_url'** - (boolean) Turns URL rewrite on/off.
- **'cache-control'** - (string) The value to header HTTP/1.1 Cache-Control.
- **'authentication'** - (array) A key pair user/pass to HTTP authentication simple access control. The array must be define is the following format `['user' => 'username', 'pass' => 'password']`. Leave it empty to turns off simple HTTP autentication access.
- **'developer_user'** - Query string variable name to turns developer mode on/off.
- **'developer_pass'** - Value to enable developer/dba mode.
- **'dba_user'** - Query string variable name to turns on/off the DBA mode.
- **'bug_authentication'** - Turns on the HTTP simple authentication to system errors log. Must by an array in following format: `['user' => 'username', 'pass' => 'password']`. Leave it empty to turns off simple HTTP autentication access to error log system.
- **'assets_source_path'** - Folder path for source of the asset files.
- **'assets_path'** - Folder path for minified asset files.
