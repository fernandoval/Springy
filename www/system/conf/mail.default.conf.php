<?php
/** \file
 *  \brief Configurações da classe de envio de email
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\copyright	Copyright (c) 2007-2014 FVAL Consultoria e Informática Ltda.\n
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
 *  \li	\c default  - Send via PHP mail (default)
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
$conf = array();

/**@}*/