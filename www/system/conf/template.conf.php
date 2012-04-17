<?php
/** \file
 *  FVAL PHP Framework for Web Applications\n
 *  Copyright (c) 2007-2011 FVAL Consultoria e Informática Ltda.
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \version 1.0.0
 *
 *  \brief Configurações da classe de templates
 */

/**
 *  \addtogroup config Configurações do sistema
 **/
/*@{*/
	/**
	 *  \addtogroup templatecfg Configurações da classe de templates
	 **/
	/*@{*/
	
		/**
		 *  \addtogroup templatecfgdevelopment Configurações para o ambiente de Desenvolvimento
		 **/
		/*@{*/
			$conf['development']['template_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates';
			$conf['development']['template_config_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates';
			$conf['development']['compiled_template_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates_c';
		/*@}*/

		/**
		 *  \addtogroup templatecfgproduction Configurações para o ambiente de Produção
		 **/
		/*@{*/
			$conf['production']['template_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates';
			$conf['production']['template_config_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates';
			$conf['production']['compiled_template_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates_c';
		/*@}*/

	/*@}*/
/*@}*/
