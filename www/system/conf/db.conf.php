<?php
/** \file
 *  FVAL PHP Framework for Web Applications\n
 *  Copyright (c) 2007-2009 FVAL Consultoria e Informática Ltda.
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \version 1.0.0
 *
 *  \brief Configurações de acesso a banco de dados
 *
 *  \c database_type determina o tipo de banco de dados.\n
 *  	Os seguintes valores são aceitos
 *  \li	\c mysql - Bancos de dados MySQL
 *  \li	\c pgsql ou \c postgresql - Banco de dados PostgreSQL
 *  \li	\c sqlite - Banco de dados SQLite
 */

/**
 *  \addtogroup config Configurações do sistema
 **/
/*@{*/
	/**
	 *  \addtogroup dbcfg Configurações de acesso a banco de dados
	 **/
	/*@{*/

		/**
		 *  \addtogroup dbcfgdevelopment Configurações para o ambiente de Desenvolvimento
		 **/
		/*@{*/
			/// Tipo do banco de dados
			$conf['development']['default']['database_type'] = 'mysql';
			/// Nome do host
			$conf['development']['default']['host_name']     = '';
			/// Nome do usuário
			$conf['development']['default']['user_name']     = '';
			/// Senha de acesso
			$conf['development']['default']['password']      = '';
			/// Banco ou schema
			$conf['development']['default']['database']      = '';
			/// Charset do banco de dados
			$conf['development']['default']['charset']       = 'utf8';
			/// Flag de conexão persistente
			$conf['development']['default']['persistent']    = false;
		/*@}*/

		/**
		 *  \addtogroup dbcfgproduction Configurações para o ambiente de Produção
		 **/
		/*@{*/
			/// Tipo do banco de dados
			$conf['production']['default']['database_type'] = 'mysql';
			/// Nome do host
			$conf['production']['default']['host_name']     = '';
			/// Nome do usuário
			$conf['production']['default']['user_name']     = '';
			/// Senha de acesso
			$conf['production']['default']['password']      = '';
			/// Banco ou schema
			$conf['production']['default']['database']      = '';
			/// Charset do banco de dados
			$conf['production']['default']['charset']       = 'utf8';
			/// Flag de conexão persistente
			$conf['production']['default']['persistent']    = true;
		/*@}*/

	/*@}*/
/*@}*/
?>