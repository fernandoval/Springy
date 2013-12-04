<?php
/** \file
 *  FVAL PHP Framework for Web Applications
 *
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *  \warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version	1.5.5
 *  \author		Fernando Val  - fernando.val@gmail.com
 *
 *  \brief Configurações do cerne do sistema
 */

/**
 *  \addtogroup config Configurações do sistema
 *  
 *  'ACTIVE_ENVIRONMENT' - Determina o ambiente ativo. São comumente utilizados 'development' e 'production' como valores dessa chave.
 *  	Se for deixada em branco, o framework irá buscar entradas de configuração para o host acessado. Por exemplo: 'www.seusite.com.br'
 */
///@{

/// Nome do sistema
$SYSTEM['SYSTEM_NAME'] = 'Nome Do Seu Sistema';
/// Versão do sistema
$SYSTEM['SYSTEM_VERSION'] = 'Versão do Seu Projeto';

/// Define o ambiente do sistema
$SYSTEM['ACTIVE_ENVIRONMENT'] = 'development';

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
/// Diretório das classes da cplicação
$SYSTEM['USER_CLASS_PATH'] = realpath($SYSTEM['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'user_classes');
/// Diretório das configurações do sistema
$SYSTEM['CONFIG_PATH'] = realpath($SYSTEM['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'conf');

/// Charset do sistema
$SYSTEM['CHARSET'] = 'UTF-8';
/// Timestamp do sistema
$SYSTEM['TIMEZONE'] = 'America/Sao_Paulo';

///@}