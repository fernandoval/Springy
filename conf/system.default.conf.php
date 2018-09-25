<?php
/**
 * General system configutations.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

/**
 *  - \c maintenance - Define se está em manutenção
 *  - \c admin_maintenance - Define se o ADMIN está em manutenção
 *  - \c 'session' - Session configurations
 *    - \c 'type' - Session type. Possible values:
 *      'file' = Standard session storage;
 *      'memcached' = Session stored using MemcacheD service;
 *      'database' = Session stored in a database table (very slow);
 *    - \c 'name' - The name of the session cookie;
 *    - \c 'domain' - The session master domain cookie;
 *    - \c 'expires' - Session expiration time in minutes;
 *    - \c 'memcached' - MemcacheD service configuration;
 *      - \c 'address' - The MemcacheD server address;
 *      - \c 'port' - The MemcacheD server port;
 *    - \c 'database' - Database configurations for session:
 *      - \c 'server' - The server configuration setting in db.conf;
 *      - \c 'table'  - The session table name.
 *  - \c system_error - Configurações de tratamento de erros da aplicação
 *    - \c save_in_database - Informa ao sistema se as ocorrências de erros devem ser armazenadas em banco de dados.
 *    - \c table_name - Informa ao sistema o nome da tabela onde as ocorrências de erro devem ser armazenadas.
 *    - \c db_server - Nome da conexão de banco de dados para armazenamento de erros. Se omitido utilizará a default.
 *    - \c create_table - Informa ao sistema se a tabela de erros deve ser criada, caso não exista.
 *  	Em caso afirmativo o sistema utlizará o script SQL armazenado no arquivo system_errors_create_table.sql que
 *  	deverá estar no diretório da biblioteca do sistema.
 */
$conf = [
    'debug'              => true,
    'ignore_deprecated'  => false,
    'rewrite_url'        => true,
    'cache-control'      => 'private, must-revalidate',
    'authentication'     => [],
    'developer_user'     => '',
    'developer_pass'     => '',
    'dba_user'           => '',
    'bug_authentication' => [],
    'assets_source_path' => sysconf('APP_PATH').DS.'assets',
    'assets_path'        => sysconf('ROOT_PATH').DS.'assets',
    'session'            => [
        'type'      => 'file', // 'file', 'memcached' or 'database'
        'name'      => 'SPRINGYSID', // The session cookie name
        'domain'    => '',
        'expires'   => 120,
        'memcached' => [
            'address' => '127.0.0.1',
            'port'    => 11211,
        ],
        'database' => [
            'server' => 'default',
            'table'  => '_sessions',
        ],
    ],
    'system_error'       => [
        'reported_errors'  => [405, 406, 408, 409, 410, 412, 413, 414, 415, 416, 417, 418, 422, 423, 424, 425, 426, 450, 499, 500, 501, 502, 504, 505],
        'save_in_database' => false,
        'table_name'       => '_system_errors',
        'db_server'        => 'default',
        'create_table'     => true,
    ],
];
