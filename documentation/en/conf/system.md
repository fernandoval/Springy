# System Configuration File (system)

The files **system.php** in `/conf` directory and **system.php** in environment
subdirectories is used by **Kernel** and other core libraries.

Like other configuration files of the framework you can add configuration
entries and change pre-defined sets, but can not delete it.

## Pre-defined configurations

*   **'debug'** - (boolean) Turns on/off the system debug.
*   **'ignore_deprecated'** - (boolean) Sets the error handler system to ignores
    or catches the deprecated funcion warnings.
*   **'rewrite_url'** - (boolean) Turns URL rewrite on/off.
*   **'cache-control'** - (string) The value to header HTTP/1.1 Cache-Control.
*   **'authentication'** - (array) A key pair user/pass to HTTP authentication
    simple access control. The array must be define is the following format
    `['user' => 'username', 'pass' => 'password']`. Leave it empty to turns off
    simple HTTP autentication access.
*   **'developer_user'** - Query string variable name to turns
    [developer mode](#developer-mode) on/off.
*   **'developer_pass'** - Value to enable developer/dba mode.
*   **'dba_user'** - Query string variable name to turns on/off the
    [DBA mode](#dba-mode).
*   **'bug_authentication'** - Turns on the HTTP simple authentication to system
    errors log. Must by an array in following format: `['user' => 'username',
    'pass' => 'password']`. Leave it empty to turns off simple HTTP
    autentication access to error log system.
*   **'assets_source_path'** - Folder path for source of the asset files.
*   **'assets_path'** - Folder path for minified asset files.
*   **'maintenance'** - Puts the system under maintenance mode and all requests
    will reply with a HTTP 503 error.
*   **'session'** - [Session configurations](#session-configurations).

### Session configurations

The `'session'` entry is an array containing other entries that configure the
framework session system.

*   **'type'** - String with the type of
    [session storage](#session-storage-types). Possible values are `'file'`,
    `'memcached'` and `'database'`.
*   **'name'** - The name of the session cookie.
*   **'domain'** - The session master domain cookie.
*   **'expires'** - Session expiration time in minutes.

## Session storage types

The framework natively supports the following types of user session control:

*   **'file'** - Storage on file. This is the default format supported by PHP.
*   **'memcached'** - Memcache service storage. Requires an external Memcache
    server or the MemcacheD service running on the computer.
*   **'database'** - Relational database table storage. It is recommended to use
    memory-type tables. If your DBMS does not support this type of table, its
    use is discouraged.

## Developer mode

Springy has configuration entries that allow system developers to turn on debug
mode in environments where it is off and access environments placed in
maintenance mode.

The `'developer_user'` and `'developer_pass'` entries are understood by the
Kernel as a special access if their values are received as key-values passed by
query string, as follows:
*`www.mysite.com/?{$developer_user}={$developer_pass}`*

For example, suppose that the configuration entries define as follows:

```php
[
    // ... other configuration entries
    'developer_user' => 'silvio',
    'developer_pass' => 'santos',
];
```

So if the developer sends the query string *?silvio=santos* into the URI, the
developer mode will be on for the entire session.

To disable developer mode without having to clear cookies or close the browser,
simply enter the following query string string: *`?{$developer_user}=off`*

Therefore the value of the `'developer_pass'` entry can never be **'off'**,
since it will not be possible to activate the developer mode.

## DBA mode

Like the [developer mode](#developer-mode), the framework has a mechanism to
enable debugging, displaying **all** the *SQL* commands executed during the
iteration.

The `'dba_user'` configuration entry defines the name of the query string
variable that enables or disables the debugging of *SQL* commands.

To enable this mode, you must first enable developer mode and then DBA mode or
do it together as follows: *`www.mysite.com/?{$developer_user}={$developer_pass}&{$dba_user}={$developer_pass}`*

For example, let's assume that the configuration entries contain the following
values:

```php
[
    // ... other configuration entries
    'developer_user' => 'silvio',
    'developer_pass' => 'santos',
    'dba_user'       => 'vemaih',
];
```

So to activate the DBA mode the developer should put the query string
*?silvio=santos&vemaih=santos* into the URI.

To disable DBA mode, the query string must contain the following:
*`?{$dba_user}=off`*
