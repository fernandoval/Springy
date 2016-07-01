<?php
/** \file
 *  \brief      General system configutations.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido.
 */

/**
 *  \addtogroup systemcfg Configurações do cerne
 *  \ingroup config.
 *
 *  As entradas de configuração dos arquivos \c system, são aplicadas ao sistema como um todo, sendo que as entradas previamente definidas não podem ser omitidas
 *  sob risco de dano à aplicação.
 *
 *  Você poderá adicionar entradas personalizadas de sua aplicação.
 *
 *  Entradas de configuração:
 *  - \c ignore_deprecated - Instrui o handler de tratamento de erros a ignorar avisos de funções depreciadas
 *  - \c bug_authentication - Habilita autenticação HTTP para acesso à página de log de erros do sistema
 *    - Esse parâmetro de configuração espera um \c array vazio ou no seguinte formato:
 *      array('user' => 'usuario', 'pass' => 'senha')
 *  - \c controller_path - Caminho do diretório de scripts de controle (controllers)
 *  - \c css_path - Caminho do diretório dos arquivos CSS
 *  - \c js_path - Caminho do diretório dos arquivos JavaScript
 *  - \c debug - Define se debug está ativo
 *  - \c maintenance - Define se está em manutenção
 *  - \c admin_maintenance - Define se o ADMIN está em manutenção
 *  - \c rewrite_url - Define se rewrite de URL está ativo
 *  - \c cache - Define se o cache está ligado
 *  - \c cache-control - Define o header HTTP/1.1 Cache-Control
 *  - \c authentication - Define o acesso autenticado por HTTP
 *    - Esse parâmetro de configuração espera um valor \c false ou um \c array no seguinte formato:
 *      array('user' => 'usuario', 'pass' => 'senha')
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
 *
 *  Os parâmetros \c developer_user e \c developer_pass informam qual parametro deverá ser passado para ligar o modo debug em servidores que não são de desenvolvimento
 *  deveserá ser usado da seguinte forma:
 *
 *  www.meusite.com.br/?{$developer_user}={$developer_pass}
 *
 *  para desligar o debug use:
 *  www.meusite.com.br/?{$developer_user}=off
 *
 *  O parâmetro \c dba_user habilita o debug de SQLs exibindo TODOS os SQLs executados na página.
 *  Para ligar este modo, primeiro deve-se habilitar o modo desenvolvedor usando o #developer_user#
 *  ex.: www.meusite.com.br/?{$developer_user}={$developer_pass}&{$dba_user}
 *
 *  Para desligar:
 *  www.meusite.com.br/?{$dba_user}=off
 *
 *  \see config
 *
 *  @{
 *  @}
 */

/**
 *  \addtogroup systemcfg_default Configurações do cerne para todos os ambientes
 *  \ingroup systemcfg.
 *
 *  As entradas colocadas nesse arquivo serão aplicadas a todos os ambientes do sistema.
 *
 *  Veja \link systemcfg Configurações do cerne \endlink para entender as entradas de configuração possíveis.
 *
 *  \see systemcfg
 */
/**@{*/

/// Configurações para todos os ambientes
$conf = [
    'debug'              => false,
    'ignore_deprecated'  => false,
    'rewrite_url'        => true,
    'cache'              => false,
    'cache-control'      => 'private, must-revalidate',
    'authentication'     => [],
    'developer_user'     => '',
    'developer_pass'     => '',
    'dba_user'           => '',
    'bug_authentication' => [],
    'assets_path'        => $GLOBALS['SYSTEM']['ROOT_PATH'].DIRECTORY_SEPARATOR.'assets',
    'controller_path'    => $GLOBALS['SYSTEM']['SYSTEM_PATH'].DIRECTORY_SEPARATOR.'controllers',
    'css_path'           => $GLOBALS['SYSTEM']['ROOT_PATH'].DIRECTORY_SEPARATOR.'css',
    'js_path'            => $GLOBALS['SYSTEM']['ROOT_PATH'].DIRECTORY_SEPARATOR.'js',
    'session'            => [
        'type'      => 'file', // 'file', 'memcached' or 'database'
        'name'      => 'SPRINGYSID', // The session cookie name
        'domain'    => '',
        'expires'   => 120,
        'memcached' => [
            'address' => '127.0.0.1',
            'port'    => 11211
        ],
        'database' => [
            'server' => 'default',
            'table'  => '_sessions',
        ],
    ],
    'system_error'       => [
        'reported_errors'  => [405, 406, 408, 409, 410, 412, 413, 414, 415, 416, 500, 501, 502, 504, 505],
        'save_in_database' => false,
        'table_name'       => 'system_errors',
        'db_server'        => 'default',
        'create_table'     => true,
    ],
];

/**@}*/
