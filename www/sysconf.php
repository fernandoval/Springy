<?php
/** \file 
 *  FVAL PHP Framework for Web Applications\n
 *  Copyright (c) 2007-2010 FVAL Consultoria e Informática Ltda.
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \version 1.0.0
 *
 *  \brief Configurações do cerne do sistema
 */

/**
 *  \addtogroup config Configurações do sistema
 **/
/*@{*/

/// Define o ambiente do sistema
$GLOBALS['SYSTEM']['ACTIVE_ENVIRONMENT'] = 'development';

/// Define se o sistema está em manutenção
$GLOBALS['SYSTEM']['MAINTENANCE'] = false;

/// Define se será usado o mini Framework CMS
$GLOBALS['SYSTEM']['CMS'] = false;

/// Caminhos das classes e arquivos de configuração
$GLOBALS['SYSTEM']['SYSTEM_PATH'] = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'system');
$GLOBALS['SYSTEM']['LIBRARY_PATH'] = realpath($GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'library');
$GLOBALS['SYSTEM']['CONTROLER_PATH'] = realpath($GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'controllers');
$GLOBALS['SYSTEM']['USER_CLASS_PATH'] = realpath($GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'user_classes');
$GLOBALS['SYSTEM']['CONFIG_PATH'] = realpath($GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'conf');

/// Configurações globais
$GLOBALS['SYSTEM']['CHARSET'] = 'UTF-8';

/*@}*/
?>