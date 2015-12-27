<?php
/** \file
 *  \brief Configurações da classe de envio de email
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 */

/**
 *  \defgroup emailcfg_production Configurações da classe de envio de email para o ambiente \c 'production'
 *  \ingroup emailcfg
 *  
 *  As entradas colocadas nesse arquivo serão aplicadas apenas ao ambiente 'production'.
 *  
 *  Seu sistema pode não possuir esse ambiente, então use-o como modelo para criação do arquivo de
 *  parâmetros de configuração para os ambientes que seu sistema possua.
 *  
 *  Veja \link emailcfg Configurações da classe de envio de email \endlink para entender as entradas de configuração possíveis.
 *  
 *  \see emailcfg
 */
/**@{*/

/// Configurações para o ambiente de Produção
$conf = array(
	'default_driver' => 'phpmailer-class',
	'mailers' => array(
		'phpmailer-class' => array(
			'driver'        => 'phpmailer',
			'protocol'      => 'smtp',
			'host'          => 'smtp.gmail.com',
			'port'          => 587,
			'cryptography'  => 'tls',
			'authenticated' => true,
			'username'      => 'you@gmail.com',
			'password'      => 'put-your-password-here',
			'debug'         => 0,
		),
		// You can remove if will not use SendGrid
		'sendgrid-api' => array(
			'driver'        => 'sendgrid',
			'apikey'        => 'put-the-sendgrid-api-key-here',
			'options'       => array(
				'protocol'  => 'https',
				// 'host'      => 'smtp.sendgrid.net',
				// 'endpoint'  => '/api/mail.send.json',
				// 'port'      => null,
			    // 'url'       => null,
				'raise_exceptions' => false,
				'turn_off_ssl_verification' => false
			)
		),
		// You can remove if will not use SendGrid
		'sendgrid-smtp' => array(
			'driver'        => 'sendgrid',
			'apikey'        => "",
			'username'      => 'put-your-sendgrid-user-here',
			'password'      => 'put-your-sendgrid-pass-here',
			'options'       => array(
				'protocol'  => 'smtp',
				'host'      => 'smtp.sendgrid.net',
				'port'      => 465,
				'raise_exceptions' => false,
				'turn_off_ssl_verification' => false
			)
		),
		// You can remove if will not use Manuel Lemos's MIME Message class
		'mimemessage' => array(
			'driver'          => 'mimemessage',
			'protocol'        => 'default',
			'host'            => 'localhost',
			'port'            => 25,
			'ssl'             => '0',
			'starttls'        => '0',
			'direct_delivery' => '0',
			'exclude_address' => '',
			'username'        => '',
			'password'        => '',
			'workstation'     => '',
			'realm'           => '',
			'auth_host'       => null,
			'debug'           => 0,
			'html_debug'      => 1,
		)
	),
);

/**@}*/