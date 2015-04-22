<?php
/** \file
 *  \brief Configurações da classe de envio de email
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 */

/**
 *  \defgroup emailcfg Configurações da classe de envio de email
 *  \ingroup config
 *  
 *  As entradas de configuração dos arquivos \c mail, são utilizadas pela classe Mail, sendo que as entradas previamente definidas não podem ser omitidas
 *  caso você utilize a classe.
 *
 *  \c method - determina o método de envio de mensagens.\n
 *  	Os seguintes valores são aceitos
 *  \li	\c smtp     - Send thru a SMTP connection
 *  \li	\c sendmail - Send using Sendmail daemon server
 *  \li	\c sendgrid - Send using SendGrid Web API.
 *  \li	\c default  - Send via PHP mail (default)
 *  \c host - SMTP server host
 *  \c port - SMTP server port (25, 465, 567)
 *  \c ssl - uses SSL (1) or not (0)
 *  \c starttls - uses StarTLS (1) or not (0)
 *  \c direct_delivery - try to deliver message directly to receipt's MTA (1) or through your relay
 *  \c exclude_address
 *  \c user - SMTP username
 *  \c realm
 *  \c workstation - do not set
 *  \c pass - SMTP password
 *  \c auth_host - do not set
 *  \c debug - turn debug messages on (1) or off (0)
 *  \c html_debug - turn debug messages HTML (1) or plain text (0). Will not work if \c debug is off.
 *  
 *  To use Sendgrid Web API method, the SendGrid-PHP library is required.
 *  To install SendGrud-PHP with Composer, adding the following in "require" section at composer.json file:
 *  
 *  "sendgrid/sendgrid": "~3.0"
 *  
 *  Or download it from https://github.com/sendgrid/sendgrid-php
 *  
 *  \see config
 *  @{
 *  @}
 */

/**
 *  \defgroup emailcfg_default Configurações da classe de envio de email para todos os ambientes
 *  \ingroup emailcfg
 *  
 *  As entradas colocadas nesse arquivo serão aplicadas a todos os ambientes do sistema.
 *  
 *  Veja \link emailcfg Configurações da classe de envio de email \endlink para entender as entradas de configuração possíveis.
 *  
 *  \see emailcfg
 */
/**@{*/

/// Entradas para todos os ambientes
$conf = array(
	// Mail to notify system errors (used by framework)
	'errors_go_to'    => 'youremail@yourdomain.com'
	
	// System Admin (used by framework)
	'system_adm_mail' => 'noreply@yourdomain.com',
	'system_adm_name' => 'System Admin',
);

/**@}*/