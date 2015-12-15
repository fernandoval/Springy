<?php
/** \file
 *  \brief Configurações da classe de templates
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 */

/**
 *  \addtogroup templatecfg Configurações da classe de templates
 *  \ingroup config
 *  
 *  As entradas de configuração dos arquivos \c template, são utilizadas pela classe Template, sendo que as entradas previamente definidas não podem ser omitidas
 *  sob risco de dano à aplicação.
 *  
 *  Você poderá adicionar entradas personalizadas de sua aplicação.
 *  
 *  Entradas de configuração:
 *  - \c template_engine - Define a classe de renderização de templates. Os valores aceitos são:\n
 *      'smarty' - para utilizar a classe Smarty como mecanismo de templates;\n
 *      'twig' - para utilizar a classe Twig como mecanismo de templates.
 *  - \c strict_variables - Quando verdadeiro a classe de templates irá ignorar variáveis inválidas e/ou indefinidas
 *  - \c debug - Se true liga o debug de template.
 *  - \c debugging_ctrl - Variável debugging_ctrl do Smarty. Aceita os valores 'URL' ou 'NONE'. (vide documentação do Smarty)
 *  - \c template_path - Caminho de disco do diretório de templates
 *  - \c template_config_path - Caminho de disco do diretório configuração de templates
 *  - \c compiled_template_path - Caminho de disco do diretório configuração de templates compilados
 *  - \c template_cached_path - Caminho de disco do diretório configuração de templates cacheados
 *  - \c use_sub_dirs - Valor booleano que informa para a classe de templates que os compilados e cache deverão ser armazenados em subdiretórios.
 *      Esse recurso é importante para aplicações com grande quantidade de páginas/templates ou que utilizem cache de template que gere grande quantidade de arquivos.
 *      Diretórios contendo milhares de arquivos criam problema de performance porque, nesses casos, o sistema de arquivos do sistema operacional demora a responder.
 *  
 *  \see config
 *  @{
 *  @}
 */

/**
 *  \defgroup templatecfg_default Configurações da classe de templates para todos os ambientes
 *  \ingroup templatecfg
 *  
 *  As entradas colocadas nesse arquivo serão aplicadas a todos os ambientes do sistema.
 *  
 *  Veja \link templatecfg Configurações da classe de templates \endlink para entender as entradas de configuração possíveis.
 *  
 *  \see templatecfg
 */
/**@{*/

/// Configurações para todos os ambientes
$conf = array(
	'template_engine'        => 'smarty',
	'strict_variables'       => true,
	'debug'                  => false,
	'debugging_ctrl'         => 'NONE',
	'template_path'          => $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates',
	'template_config_path'   => $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'templates_conf',
	'compiled_template_path' => $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'tpl_comp',
	'template_cached_path'   => $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'tpl_cache',
	'use_sub_dirs'           => false,
	'errors'                 => array(
		404 => '_error404',
		500 => '_error500',
		503 => '_error503'
	)
);

/**@}*/