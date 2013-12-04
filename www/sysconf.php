<?php
/** \file
 *  FVAL PHP Framework for Web Applications
 *
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *  \warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version	1.5.6
 *  \author		Fernando Val  - fernando.val@gmail.com
 *
 *  \brief Configurações do cerne do sistema
 */

/**
 *  \addtogroup config Configurações do sistema
 *  
 *  Definição das entradas de configuração:
 *  
 *  \li 'SYSTEM_NAME' - Nome do sistema.
 *  \li 'SYSTEM_VERSION' - Versão do sistema.
 *  \li 'ACTIVE_ENVIRONMENT' - Determina o ambiente ativo. São comumente utilizados 'development' e 'production' como valores dessa chave.
 *    Se for deixada em branco, o framework irá buscar entradas de configuração para o host acessado. Por exemplo: 'www.seusite.com.br'
 *  \li 'ENVIRONMENT_ALIAS' - Array contendo um conjunto \c chave => \c valor, onde \c chave representa um apelido para o ambiente \c valor.
 *    A \c chave pode ser uma expressão regular.
 *  \li 'ROOT_PATH' - Diretório root da aplicação.
 *  \li 'SYSTEM_PATH' - Diretório do sistema.
 *  \li 'LIBRARY_PATH' - Diretório da biblioteca do sistema.
 *  \li 'CONTROLER_PATH' - Diretório das classes da aplicação.
 *  \li 'USER_CLASS_PATH' - Diretório das classes da aplicação.
 *  \li 'CONFIG_PATH' - Diretório das configurações do sistema.
 *  \li 'CHARSET' - Charset do sistema.
 *  \li 'TIMEZONE' - Timestamp do sistema
 */
///@{

/// Nome do sistema
$SYSTEM['SYSTEM_NAME'] = 'Nome Do Seu Sistema';
/// Versão do sistema
$SYSTEM['SYSTEM_VERSION'] = 'Versão do Seu Projeto';

/// Define o ambiente do sistema
$SYSTEM['ACTIVE_ENVIRONMENT'] = 'development';
/// Define os ambientes similares
$SYSTEM['ENVIRONMENT_ALIAS'] = array(
	'homol(ogation)?' => 'development',
	'(www\.)?seusite\.com(\.br)?' => 'development'
);

/// Define se será usado o mini Framework CMS
$SYSTEM['CMS'] = false;

/// Diretório root da aplicação
$SYSTEM['ROOT_PATH'] = realpath(dirname(__FILE__));
/// Diretório do sistema
$SYSTEM['SYSTEM_PATH'] = realpath($SYSTEM['ROOT_PATH'] . DIRECTORY_SEPARATOR . 'system');
/// Diretório da biblioteca do sistema
$SYSTEM['LIBRARY_PATH'] = realpath($SYSTEM['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'library');
/// Diretório das controladoras
$SYSTEM['CONTROLER_PATH'] = realpath($SYSTEM['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'controllers');
/// Diretório das classes da aplicação
$SYSTEM['USER_CLASS_PATH'] = realpath($SYSTEM['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'user_classes');
/// Diretório das configurações do sistema
$SYSTEM['CONFIG_PATH'] = realpath($SYSTEM['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'conf');

/// Charset do sistema
$SYSTEM['CHARSET'] = 'UTF-8';
/// Timestamp do sistema
$SYSTEM['TIMEZONE'] = 'America/Sao_Paulo';

///@}