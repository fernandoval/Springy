<?php
/** \file
 *  \brief Configurações de acesso a banco de dados
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 */

/**
 *  \defgroup dbcfg_development Configurações de acesso a banco de dados para o ambiente 'development'
 *  \ingroup dbcfg
 *  
 *  As entradas colocadas nesse arquivo serão aplicadas apenas ao ambiente 'development'.
 *  
 *  Seu sistema pode não possuir esse ambiente, então use-o como modelo para criação do arquivo de
 *  parâmetros de configuração para os ambientes que seu sistema possua.
 *  
 *  Veja \link dbcfg Configurações de acesso a banco de dados \endlink para entender as entradas de configuração possíveis.
 *  
 *  \see dbcfg
 */
/**@{*/

/// Configurações para o ambiente de Desenvolvimento
$conf = array(
	'round_robin' => array(
		'type' => 'file',
		'server_addr' => $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates_c',
		'server_port' => 11211
	),
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

/**@}*/