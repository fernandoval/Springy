<?php
/** \file
 *  \brief Configurações da classe de envio de email
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 */

/**
 *  \defgroup emailcfg_development Configurações da classe de envio de email para o ambiente \c 'development'
 *  \ingroup emailcfg
 *  
 *  As entradas colocadas nesse arquivo serão aplicadas apenas ao ambiente 'development'.
 *  
 *  Seu sistema pode não possuir esse ambiente, então use-o como modelo para criação do arquivo de
 *  parâmetros de configuração para os ambientes que seu sistema possua.
 *  
 *  Veja \link emailcfg Configurações da classe de envio de email \endlink para entender as entradas de configuração possíveis.
 *  
 *  \see emailcfg
 */
/**@{*/

/// Configurações para o ambiente de Desenvolvimento
$conf = array(
	'method'          => 'default',
	'host'            => 'localhost',
	'port'            => '25',
	'ssl'             => '0',
	'starttls'        => '0',
	'direct_delivery' => '0',
	'exclude_address' => '',
	'user'            => '',
	'realm'           => '',
	'workstation'     => '',
	'pass'            => '',
	'exclude_address' => '',
	'auth_host'       => '',
	'debug'           => 0,
	'html_debug'      => 1,
	'errors_go_to'    => NULL
);

/**@}*/