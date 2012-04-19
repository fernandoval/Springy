<?php
/** \file
 *  FVAL PHP Framework for Web Applications\n
 *  Copyright (c) 2007-2011 FVAL Consultoria e Informática Ltda.
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \version 1.0.1
 *
 *  \brief Configurações da classe URI
 *
 *  How to use: [pt-br] : Exemplo de código PHP de como usar esta classe
 *
 *	$conf['default']['register_method_set_common_urls'] = array('class' => 'Urls', 'method' => 'setCommon', 'static' => true) // executa: Urls::setCommon();
 *	$conf['default']['register_method_set_common_urls'] = array('class' => 'Urls', 'method' => 'setCommon', 'static' => false) // executa: (new Urls())->setCommon();
 *	$conf['default']['register_method_set_common_urls'] = array('class' => 'Urls', 'static' => false) // executa: new Urls();
 *
 */

/**
 *  \addtogroup config Configurações do sistema
 **/
/*@{*/
	/**
	 *  \addtogroup uricfg Configurações da classe de tratamento de URI/URL
	 **/
	/*@{*/

		/**
		 *  \addtogroup uricfgdefault Configurações padrão
		 **/
		/*@{*/
			$conf['default']['routes'] = array(
				'home(\/)*(\?(.*))' => array('segment' => 0, 'controller' => 'index'),
			);
			
			/// URI da aplicação
			$conf['default']['system_root'] = '/';
		
			$conf['default']['register_method_set_common_urls'] = NULL;
			$conf['default']['common_urls'] = array();
		/*@}*/

	/*@}*/
/*@}*/
