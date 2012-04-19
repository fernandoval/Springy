<?php
/** \file
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2011 FVAL Consultoria e Informсtica Ltda.\n
 *	Copyright (c) 2007-2011 Fernando Val\n
 *	Copyright (c) 2009-2011 Lucas Cardozo
 *
 *  \warning Este arquivo щ parte integrante do framework e nуo pode ser omitido
 *
 *  \version 1.1.0
 *
 *  \brief Configuraчѕes de sessуo
 *
 *	\c type determina o tipo de tratamento de sessуo.\n
 *		Os seguintes valores sуo aceitos:\n
 *	\li \c 'std' - tratamento de sessуo padrуo utilizando session_start();
 *	\li \c 'db' - tratamento de sessуo em banco de dados;
 *	\li \c 'memcached' - tratamento de sessуo utilizando Memcached (http://www.php.net/manual/en/book.memcached.php)
 */

/**
 *  \addtogroup config Configuraчѕes do sistema
 **/
/*@{*/
	/**
	 *  \addtogroup sessioncfgdevelopment Configuraчѕes para o ambiente de Desenvolvimento
	 **/
	/*@{*/
		/// Define o tipo de sessуo
		$conf['development']['type'] = 'std';
		/// Define o endereчo servidor de banco ou memcadhed
		$conf['development']['server_addr'] = '127.0.0.1';
		/// Define a porta do servidor de banco ou memcadhed
		$conf['development']['server_port'] = 11211;
		/// Define o tempo de validade da sessуo em minutos
		$conf['development']['master_domain'] = "";
		/// Define o tempo de validade da sessуo em minutos
		$conf['development']['expires'] = 120;
		/// Define o nome da tabela para sessѕes em banco de dados
		$conf['development']['table_name'] = 'sessao_usuario';
		/// Define o nome da coluna do id da sessуo
		$conf['development']['id_column'] = 'id_sessao';
		/// Define o nome da coluna do valor da sessуo
		$conf['development']['value_column'] = 'valor';
		/// Define o nome da coluna da data de atualizaчуo da sessуo
		$conf['development']['update_column'] = 'data_atualizacao';
	/*@}*/

	/**
	 *  \addtogroup sessioncfgproduction Configuraчѕes para o ambiente de Produчуo
	 **/
	/*@{*/
		/// Define o tipo de sessуo
		$conf['production']['type'] = 'std';
		/// Define o servidor de banco ou memcadhed
		$conf['production']['server_addr'] = '127.0.0.1';
		/// Define a porta do servidor de banco ou memcadhed
		$conf['production']['server_port'] = 11211;
		/// Define o tempo de validade da sessуo em minutos
		$conf['production']['master_domain'] = "";
		/// Define o tempo de validade da sessуo em minutos
		$conf['production']['expires'] = 120;
		/// Define o nome da tabela para sessѕes em banco de dados
		$conf['production']['table_name'] = 'sessao_usuario';
		/// Define o nome da coluna do id da sessуo
		$conf['production']['id_column'] = 'id_sessao';
		/// Define o nome da coluna do valor da sessуo
		$conf['production']['value_column'] = 'valor';
		/// Define o nome da coluna da data de atualizaчуo da sessуo
		$conf['production']['update_column'] = 'data_atualizacao';
	/*@}*/
/*@}*/
