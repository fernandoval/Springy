<?php
/** \file
 *  \brief Configurações de acesso a banco de dados
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *  \addtogroup	config
 */
/**@{*/

/**
 *  \addtogroup dbcfg Configurações de acesso a banco de dados
 *  
 *  Entradas de configuração:
 *  \c database_type determina o tipo de banco de dados.\n
 *  	Os seguintes valores são aceitos
 *  \li	\c mysql - Bancos de dados MySQL
 *  \li	\c pgsql ou \c postgresql - Banco de dados PostgreSQL
 *  \li	\c sqlite - Banco de dados SQLite
 *	\li \c pool - Configuração para pool de conexão de banco de dados por round robin.\n
 *		Nesse caso, o host_name deverá ser um array com as entradas de configuração.
 *
 *	\c Entradas da configuração de conexão:
 *	\li \c database_type - Tipo do banco de dados
 *	\li \c host_name - Nome do host. Se database_type for 'pool', essa entrada deve ser um array contendo das entradas de configuração do pool de conexão.
 *	\li \c user_name - Nome do usuário
 *	\li \c password - Senha de acesso
 *	\li \c database - Banco ou schema
 *	\li \c charset - Charset do banco de dados
 *	\li \c persistent - Flag de conexão persistente (true | false)
 *
 *	\c Entradas da configuração de pool por round_robin:
 *	\li \c round_robin > type - Tipo de controle do round robin (file | memcached)
 *	\li \c round_robin > server_addr - Endereço do servidor memcached ou caminho do arquivo de controle
 *	\li \c round_robin > server_port - Porta do servidor memcached
 */
/**@{*/

/// Configurações para o ambiente de Desenvolvimento
$conf['development'] = array(
	'default' => array(
		'database_type' => 'mysql',
		'host_name'     => '',
		'user_name'     => '',
		'password'      => '',
		'database'      => '',
		'charset'       => 'utf8',
		'persistent'    => false
	)
);

/// Configurações para o ambiente de Produção
$conf['production'] = array(
	'default' => array(
		'database_type' => 'mysql',
		'host_name'     => '',
		'user_name'     => '',
		'password'      => '',
		'database'      => '',
		'charset'       => 'utf8',
		'persistent'    => false
	)
);

/*@}*/
/*@}*/
