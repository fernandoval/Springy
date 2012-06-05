<?php
/** \file 
 *  FVAL PHP Framework for Web Applications
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \version 1.2.1
 *
 *  \brief Configurações do cerne do sistema
 */

/**
 *  \addtogroup config Configurações do sistema
 **/
/*@{*/
/// Define o ambiente do sistema
$SYSTEM['SITE_NAME'] = 'NomeDoSeuSistema';

/// Define o ambiente do sistema
$SYSTEM['ACTIVE_ENVIRONMENT'] = 'development';

/// Define se será usado o mini Framework CMS
$SYSTEM['CMS'] = false;

/// Caminhos das classes e arquivos de configuração
$SYSTEM['SYSTEM_PATH'] = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'system');
$SYSTEM['LIBRARY_PATH'] = realpath($SYSTEM['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'library');
$SYSTEM['CONTROLER_PATH'] = realpath($SYSTEM['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'controllers');
$SYSTEM['USER_CLASS_PATH'] = realpath($SYSTEM['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'user_classes');
$SYSTEM['CONFIG_PATH'] = realpath($SYSTEM['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'conf');

/// Configurações globais
$SYSTEM['CHARSET'] = 'UTF-8';
$SYSTEM['TIMEZONE'] = 'America/Sao_Paulo';

/*@}*/
