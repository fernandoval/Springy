<?php
/** \file
 *  \brief Configurações da classe de templates
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *  \addtogroup	config
 */
/**@{*/

/**
 *  \addtogroup templatecfg Configurações da classe de templates
 */
/**@{*/

/**
 *  @name Configurações para todos os ambientes
 */
///@{
$conf['default']['template_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates';
$conf['default']['template_config_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates_conf';
$conf['default']['compiled_template_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates_c';
$conf['default']['template_cached_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates_cached';
///@}

/**
 *  @name Configurações para o ambiente de Desenvolvimento
 */
///@{
// $conf['development']['template_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates';
// $conf['development']['template_config_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates_conf';
// $conf['development']['compiled_template_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates_c';
///@}

/**
 *  @name Configurações para o ambiente de Produção
 **/
///@{
// $conf['production']['template_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates';
// $onf['production']['template_config_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates_conf';
// $conf['production']['compiled_template_path'] = $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates_c';
///@}

/**@}*/
/**@}*/
