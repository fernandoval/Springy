<?php
/** \file
 *  \brief Configurações de sessão
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.
 *  \addtogroup	config
 */
/**@{*/

/**
 *  \addtogroup sessioncfg Configurações de SESSÃO
 *
 *  Entradas de configuração:
 *	- \c type - determina o tipo de tratamento de sessão.\n Os seguintes valores são aceitos:\n
 *	  - \c 'std' - tratamento de sessão padrão utilizando session_start();
 *	  - \c 'db' - tratamento de sessão em banco de dados;
 *	  - \c 'memcached' - tratamento de sessão utilizando Memcached (http://www.php.net/manual/en/book.memcached.php)
 *  - \c server_addr - endereço servidor de banco ou memcadhed (quando tipo de controle de sessão não é o padrão)
 */
/**@{*/

/**
 *  @name Configurações para o ambiente de Desenvolvimento
 */
///@{
/// Define o tipo de sessão
$conf['development']['type'] = 'std';
/// Define o endereço servidor de banco ou memcadhed
$conf['development']['server_addr'] = '127.0.0.1';
/// Define a porta do servidor de banco ou memcadhed
$conf['development']['server_port'] = 11211;
/// Define o tempo de validade da sessão em minutos
$conf['development']['master_domain'] = "";
/// Define o tempo de validade da sessão em minutos
$conf['development']['expires'] = 120;
/// Define o nome da tabela para sessões em banco de dados
$conf['development']['table_name'] = 'sessao_usuario';
/// Define o nome da coluna do id da sessão
$conf['development']['id_column'] = 'id_sessao';
/// Define o nome da coluna do valor da sessão
$conf['development']['value_column'] = 'valor';
/// Define o nome da coluna da data de atualização da sessão
$conf['development']['update_column'] = 'data_atualizacao';
///@}

/**
 *  @name Configurações para o ambiente de Produção
 */
///@{
/// Define o tipo de sessão
$conf['production']['type'] = 'std';
/// Define o servidor de banco ou memcadhed
$conf['production']['server_addr'] = '127.0.0.1';
/// Define a porta do servidor de banco ou memcadhed
$conf['production']['server_port'] = 11211;
/// Define o tempo de validade da sessão em minutos
$conf['production']['master_domain'] = "";
/// Define o tempo de validade da sessão em minutos
$conf['production']['expires'] = 120;
/// Define o nome da tabela para sessões em banco de dados
$conf['production']['table_name'] = 'sessao_usuario';
/// Define o nome da coluna do id da sessão
$conf['production']['id_column'] = 'id_sessao';
/// Define o nome da coluna do valor da sessão
$conf['production']['value_column'] = 'valor';
/// Define o nome da coluna da data de atualização da sessão
$conf['production']['update_column'] = 'data_atualizacao';
///@}
		
/**@}*/
/**@}*/
