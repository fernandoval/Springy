<?php
/** \file
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2012 FVAL Consultoria e Informática Ltda.\n
 *	Copyright (c) 2007-2012 Fernando Val\n
 *	Copyright (c) 2009-2012 Lucas Cardozo
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \version 1.2.0
 *
 *  \brief Configurações gerais do sistema
 */

/**
 *  \addtogroup config Configurações do sistema
 **/
/*@{*/
	/**
	 *  \addtogroup systemcfgdebugonline Configurações padrão
	 **/
	/*@{*/
		// informa qual parametro deverá ser passado para ligar o modo debug em servidores q não são de desenvolvimento
		// deveserá ser usado da seguinte forma:
		// www.meusite.com.br/?{$developer_user}={$developer_pass}
		//
		// para desligar o debug use:
		// www.meusite.com.br/?{$developer_user}=off
		$conf['default']['developer_user'] = '';
		$conf['default']['developer_pass'] = '';
		
		// Habilita o debug de SQLs exibindo TODOS os SQLs executados na página.
		// Para ligar este modo, primeiro deve-se habilitar o modo desenvolvedor usando o #developer_user#
		// ex.: www.meusite.com.br/?{$developer_user}={$developer_pass}&{$dba_user}
		//
		// para desligar:
		// www.meusite.com.br/?{$dba_user}=off
		$conf['default']['dba_user'] = '';
	/*@}*/
	
	/**
	 *  \addtogroup systemcfgdevelopment Configurações para o ambiente de Desenvolvimento
	 **/
	/*@{*/
		/// Define se debug está ativo
		$conf['development']['debug'] = true;
		/// Define se está em manutenção
		$conf['development']['maintenance'] = false;
		/// Define se o ADMIN está em manutenção
		$conf['development']['admin_maintenance'] = false;
		/// Define se rewrite de URL está ativo
		$conf['development']['rewrite_url'] = true;
		/// Define se o cache está ligado
		$conf['development']['cache'] = false;
		/// Define o acesso autenticado por sistema
		$conf['development']['authentication'] = array('user' => '', 'pass' => '');

		/// Caminho do diretório de scripts de controle (controllers)
		$conf['development']['controller_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'controllers';

		/** \deprecated */
		$conf['development']['ext_file_url'] = '';
	/*@}*/

	/**
	 *  \addtogroup systemcfgproduction Configurações para o ambiente de Produção
	 **/
	/*@{*/
		/// Define se debug está ativo
		$conf['production']['debug'] = false;
		/// Define se está em manutenção
		$conf['production']['maintenance'] = false;
		/// Define se o ADMIN está em manutenção
		$conf['production']['admin_maintenance'] = false;
		/// Define se rewrite de URL está ativo
		$conf['production']['rewrite_url'] = true;
		/// Define se o cache está ligado
		$conf['production']['cache'] = false;
		/// Define o acesso autenticado por sistema
		$conf['production']['authentication'] = false;

		/// Caminho do diretório de scripts de controle (controllers)
		$conf['production']['controller_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'controllers';

		/** \deprecated */
		$conf['production']['ext_file_url'] = '';
	/*@}*/
/*@}*/
