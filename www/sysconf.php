<?php
/** \file
 *  FVAL PHP Framework for Web Applications
 *
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *  \warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version	1.4.1
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

/// Define o ambiente do sistema
$SYSTEM['SITE_NAME'] = 'Nome Do Seu Sistema';

/// Define a versão do sistema
$SYSTEM['PROJECT_VERSION'] = 'Versão do Seu Projeto';

/// Define o ambiente do sistema
$SYSTEM['ACTIVE_ENVIRONMENT'] = 'development';

/// Define se será usado o mini Framework CMS
$SYSTEM['CMS'] = false;

/// Caminhos das classes e arquivos de configuração
$SYSTEM['ROOT_PATH'] = realpath(dirname(__FILE__));
$SYSTEM['SYSTEM_PATH'] = realpath($SYSTEM['ROOT_PATH'] . DIRECTORY_SEPARATOR . 'system');
$SYSTEM['LIBRARY_PATH'] = realpath($SYSTEM['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'library');
$SYSTEM['CONTROLER_PATH'] = realpath($SYSTEM['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'controllers');
$SYSTEM['USER_CLASS_PATH'] = realpath($SYSTEM['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'user_classes');
$SYSTEM['CONFIG_PATH'] = realpath($SYSTEM['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'conf');

/// Configurações globais
$SYSTEM['CHARSET'] = 'UTF-8';
$SYSTEM['TIMEZONE'] = 'America/Sao_Paulo';

///@}