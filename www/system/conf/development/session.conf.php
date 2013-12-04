<?php
/** \file
 *  \brief Configurações de sessão
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.
 *  \addtogroup	config
 */
/**@{*/

/**
 *  \addtogroup sessioncfg Configurações de SESSÃO
 *
 *  Entradas de configuração:
 *	- \c type - determina o tipo de tratamento de sessão.\n Os seguintes valores são aceitos:\n
 *	  - \c 'std' - tratamento de sessão padrão utilizando session_start();
 *	  - \c 'db' - tratamento de sessão em banco de dados;
 *	  - \c 'memcached' - tratamento de sessão utilizando Memcached (http://www.php.net/manual/en/book.memcached.php)
 *  - \c server_addr - endereço servidor de banco ou memcadhed (quando tipo de controle de sessão não é o padrão)
 *  - \c server_port - porta do servidor de banco ou memcadhed (quando tipo de controle de sessão não é o padrão)
 *  - \c master_domain - domínio master para o cookie de sessão
 *  - \c expires - define o tempo de validade da sessão em minutos
 *  - \c table_name - define o nome da tabela para sessões em banco de dados (quando o tipo de controle for 'db')
 *  - \c id_column - define o nome da coluna do id da sessão (quando o tipo de controle for 'db')
 *  - \c value_column - define o nome da coluna do valor da sessão (quando o tipo de controle for 'db')
 *  - \c update_column - define o nome da coluna da data de atualização da sessão (quando o tipo de controle for 'db')
 */
/**@{*/

/// Configurações para o ambiente de Desenvolvimento
$conf = array(
	'type'          => 'std',
	'server_addr'   => '127.0.0.1',
	'server_port'   => 11211,
	'master_domain' => "",
	'expires'       => 120,
	'table_name'    => 'sessao_usuario',
	'id_column'     => 'id_sessao',
	'value_column'  => 'valor',
	'update_column' => 'data_atualizacao'
);

/**@}*/
/**@}*/
