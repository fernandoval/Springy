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
 *  \c $conf[<environment>]['method'] determina o método de envio de mensagens.\n
 *  	Os seguintes valores são aceitos
 *  \li	\c smtp     - Send thru a SMTP connection
 *  \li	\c sendmail - Send using Sendmail daemon server
 *  \li	\c default  - Send via PHP mail (default)
 */
/**@{*/

/**
 *  @name Configurações para o ambiente de Desenvolvimento
 */
///@{
$conf['development']['method'] = 'default';
$conf['development']['host'] = 'localhost';
$conf['development']['port'] = '25';
$conf['development']['ssl'] = '0';
$conf['development']['starttls'] = '0';
$conf['development']['direct_delivery'] = '0';
$conf['development']['exclude_address'] = '';
$conf['development']['user'] = '';
$conf['development']['realm'] = '';
$conf['development']['workstation'] = '';
$conf['development']['pass'] = '';
$conf['development']['exclude_address'] = '';
$conf['development']['auth_host'] = '';
$conf['development']['debug'] = 0;
$conf['development']['html_debug'] = 1;
$conf['development']['errors_go_to'] = NULL;
///@}

/**
 *  @name Configurações para o ambiente de Produção
 */
///@{
$conf['production']['method'] = 'default';
$conf['production']['host'] = 'localhost';
$conf['production']['port'] = '25';
$conf['production']['ssl'] = '0';
$conf['production']['starttls'] = '0';
$conf['production']['direct_delivery'] = '0';
$conf['production']['exclude_address'] = '';
$conf['production']['user'] = '';
$conf['production']['realm'] = '';
$conf['production']['workstation'] = '';
$conf['production']['pass'] = '';
$conf['production']['exclude_address'] = '';
$conf['production']['auth_host'] = '';
$conf['production']['debug'] = 0;
$conf['production']['hlmt_debug'] = 0;
$conf['production']['errors_go_to'] = 'yourname@yourisp.com';
///@}

/**@}*/
/**@}*/
