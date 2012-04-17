<?php
/** \file
 *  FVAL PHP Framework for Web Applications\n
 *  Copyright (c) 2007-2009 FVAL Consultoria e Informática Ltda.
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \version 1.0.0
 *
 *  \brief Configurações da classe SOAP
 */

/**
 *  \addtogroup config Configurações do sistema
 **/
/*@{*/
	/**
	 *  \addtogroup soapcfg Configurações da classe de envio de email
	 **/
	/*@{*/

		/**
		 *  \addtogroup soapcfgdevelopment Configurações para o ambiente de Desenvolvimento
		 **/
		/*@{*/
			$conf['development']['proxyhost'] = '';
			$conf['development']['proxyport'] = 0;
			$conf['development']['proxyusername'] = '';
			$conf['development']['proxypassword'] = '';
			$conf['development']['useCURL'] = false;
		/*@}*/

		/**
		 *  \addtogroup soapecfgproduction Configurações para o ambiente de Produção
		 **/
		/*@{*/
			$conf['production']['proxyhost'] = '';
			$conf['production']['proxyport'] = 0;
			$conf['production']['proxyusername'] = '';
			$conf['production']['proxypassword'] = '';
			$conf['production']['useCURL'] = false;
		/*@}*/

	/*@}*/
/*@}*/
?>