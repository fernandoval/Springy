<?php
/** \file
 *  \brief Configurações da classe de envio de email
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *  \addtogroup	config
 */
/**@{*/

/**
 *  \addtogroup emailcfg Configurações da classe de envio de email
 *
 *  \c method - determina o método de envio de mensagens.\n
 *  	Os seguintes valores são aceitos
 *  \li	\c smtp     - Send thru a SMTP connection
 *  \li	\c sendmail - Send using Sendmail daemon server
 *  \li	\c default  - Send via PHP mail (default)
 */
/**@{*/

/// Configurações para o ambiente de Desenvolvimento
$conf['development'] = array(
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

/// Configurações para o ambiente de Produção
$conf['production'] = array(
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
	'hlmt_debug'      => 0,
	'errors_go_to'    => 'yourname@yourisp.com'
);

/**@}*/
/**@}*/
