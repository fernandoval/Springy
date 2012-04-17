<?php
/** \file
 *  FVAL PHP Framework for Web Applications\n
 *  Copyright (c) 2007-2009 FVAL Consultoria e Informática Ltda.
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \version 1.0.0
 *
 *  \brief Configurações gerais do sistema
 */

/**
 *  \addtogroup config Configurações do sistema
 **/
/*@{*/
	/**
	 *  \addtogroup systemcfgdevelopment Configurações para o ambiente de Desenvolvimento
	 **/
	/*@{*/
		/// Define se ambiente é de desenvolvimento
		$conf['development']['development'] = true;
		/// Define se debug está ativo
		$conf['development']['debug'] = true;
		/// Define se rewrite de URL está ativo
		$conf['development']['rewrite_url'] = true;
		/// Define se o cache está ligado
		$conf['development']['cache'] = false;

		/// Caminho do diretório de scriptos de controle (controllers)
		$conf['development']['controller_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'controllers';

		/// URI da aplicação
		$conf['development']['uri'] = $_SERVER['HTTP_HOST'];
		
		/** \deprecated */
		$conf['development']['ext_file_url'] = '';
	/*@}*/

	/**
	 *  \addtogroup systemcfgproduction Configurações para o ambiente de Produção
	 **/
	/*@{*/
		/// Define se ambiente é de desenvolvimento
		$conf['production']['development'] = false;
		/// Define se debug está ativo
		$conf['production']['debug'] = false;
		/// Define se rewrite de URL está ativo
		$conf['production']['rewrite_url'] = true;
		/// Define se o cache está ligado
		$conf['production']['cache'] = true;

		/// Caminho do diretório de scriptos de controle (controllers)
		$conf['production']['controller_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'controllers';

		/// URI da aplicação
		$conf['production']['uri'] = $_SERVER['HTTP_HOST'];

		/** \deprecated */
		$conf['production']['ext_file_url'] = '';
	/*@}*/
/*@}*/
?>