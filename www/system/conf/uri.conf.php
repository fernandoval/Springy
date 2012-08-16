<?php
/** \file
 *  FVAL PHP Framework for Web Applications\n
 *  Copyright (c) 2007-2011 FVAL Consultoria e Informática Ltda.
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \version 1.3.4
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
			/// Rotas alternativas para controladoras
			$conf['default']['routes'] = array(
				'home(\/)*(\?(.*))*' => array('segment' => 0, 'controller' => 'index'),
			);

			/// Redirecionamentos
			$conf['default']['redirects'] = array(
				'404' => array('segments' => array(), 'get' => array(), 'force_rewrite' => false, 'host' => 'dynamic', 'type' => 301),
			);

			/// URI da aplicação
			$conf['default']['system_root'] = '/';

			$conf['default']['register_method_set_common_urls'] = NULL;
			$conf['default']['common_urls'] = array();
			$conf['default']['redirect_last_slash'] = true;
		/*@}*/
		/**
		 *  \addtogroup uricfgdevelopment Configurações para o ambiente de Desenvolvimento
		 **/
		/*@{*/
			/// Root controllers path por HOST
			$conf['development']['host_controller_path'] = array(
				'host.seusite.localhost' => array('diretorio'),
			);

			$conf['development']['dynamic'] = $_SERVER['HTTP_HOST'];
			$conf['development']['static'] = $_SERVER['HTTP_HOST'];
			$conf['development']['secure'] = $_SERVER['HTTP_HOST'];
		/*@}*/
		/**
		 *  \addtogroup uriecfgproduction Configurações para o ambiente de Produção
		 **/
		/*@{*/
			/// Root controllers path por HOST
			$conf['production']['host_controller_path'] = array(
				'host.seusite.com' => array('diretorio'),
			);

			$conf['production']['dynamic'] = isset($_SERVER['HTTPS']) ? 'http://www.hotelurbano.com.br' : $_SERVER['HTTP_HOST'];
			$conf['production']['static'] = isset($_SERVER['HTTPS']) ? $_SERVER['HTTP_HOST'] : 'cdn.hotelurbano.com.br';
			$conf['production']['secure'] = 'https://secure.hotelurbano.com.br';
		/*@}*/
	/*@}*/
/*@}*/
