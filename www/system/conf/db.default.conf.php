<?php
/** \file
 *  \brief Configurações de acesso a banco de dados
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \copyright	Copyright (c) 2007-2014 FVAL Consultoria e Informática Ltda.\n
 */

/**
 *  \defgroup dbcfg Configurações de acesso a banco de dados
 *  \ingroup config
 *  
 *  As entradas de configuração dos arquivos \c db, são utilizadas pela classe DB, sendo que as entradas previamente definidas não podem ser omitidas
 *  sob risco de dano à aplicação.
 *  
 *  Você poderá adicionar entradas personalizadas de sua aplicação.
 *  
 *  Entradas de configuração:
 *  \c database_type determina o tipo de banco de dados.\n
 *  	Os seguintes valores são aceitos
 *  \li	\c mysql - Bancos de dados MySQL
 *  \li	\c pgsql ou \c postgresql - Banco de dados PostgreSQL
 *  \li	\c sqlite - Banco de dados SQLite
 *  \li \c pool - Configuração para pool de conexão de banco de dados por round robin.\n
 *    Nesse caso, o host_name deverá ser um array com as entradas de configuração.
 *
 *  \c Entradas da configuração de conexão:
 *  \li \c database_type - Tipo do banco de dados
 *  \li \c host_name - Nome do host. Se database_type for 'pool', essa entrada deve ser um array contendo das entradas de configuração do pool de conexão.
 *  \li \c user_name - Nome do usuário
 *  \li \c password - Senha de acesso
 *  \li \c database - Banco ou schema
 *  \li \c charset - Charset do banco de dados
 *  \li \c persistent - Flag de conexão persistente (true | false)
 *
 *  \c Entradas da configuração de pool por round_robin:
 *  \li \c round_robin > type - Tipo de controle do round robin (file | memcached)
 *  \li \c round_robin > server_addr - Endereço do servidor memcached ou caminho do arquivo de controle
 *  \li \c round_robin > server_port - Porta do servidor memcached
 *  
 *  \c Entradas da configuração de cache de consulta em memcached:
 *  \li \c cache > type - Tipo de sistema de cache (off | memcached)
 *  \li \c cache > server_addr - Endereço do servidor memcached ou caminho do arquivo de controle
 *  \li \c cache > server_port - Porta do servidor memcached
 *  
 *  \see config
 *  @{
 *  @}
 */

/**
 *  \defgroup dbcfg_default Configurações de acesso a banco de dados para todos os ambientes
 *  \ingroup dbcfg
 *  
 *  As entradas colocadas nesse arquivo serão aplicadas a todos os ambientes do sistema.
 *  
 *  Veja \link dbcfg Configurações de acesso a banco de dados \endlink para entender as entradas de configuração possíveis.
 *  
 *  \see dbcfg
 */
/**@{*/

/// Entradas para todos os ambientes
$conf = array();

/**@}*/