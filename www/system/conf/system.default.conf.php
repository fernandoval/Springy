<?php
/** \file
 *  \brief Configurações gerais do sistema
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 */

/**
 *  \addtogroup systemcfg Configurações do cerne
 *  \ingroup config
 *  
 *  As entradas de configuração dos arquivos \c system, são aplicadas ao sistema como um todo, sendo que as entradas previamente definidas não podem ser omitidas
 *  sob risco de dano à aplicação.
 *  
 *  Você poderá adicionar entradas personalizadas de sua aplicação.
 *  
 *  Entradas de configuração:
 *  - \c ignore_deprecated - Instrui o handler de tratamento de erros a ignorar avisos de funções depreciadas
 *  - \c controller_path - Caminho do diretório de scripts de controle (controllers)
 *  - \c css_path - Caminho do diretório dos arquivos CSS
 *  - \c js_path - Caminho do diretório dos arquivos JavaScript
 *  - \c debug - Define se debug está ativo
 *  - \c maintenance - Define se está em manutenção
 *  - \c admin_maintenance - Define se o ADMIN está em manutenção
 *  - \c rewrite_url - Define se rewrite de URL está ativo
 *  - \c cache - Define se o cache está ligado
 *  - \c cache-control - Define o header HTTP/1.1 Cache-Control
 *  - \c authentication - Define o acesso autenticado por HTTP
 *    - Esse parâmetro de configuração espera um valor \c false ou um \c array no seguinte formato:
 *      array('user' => 'usuario', 'pass' => 'senha')
 *  
 *  Os parâmetros \c developer_user e \c developer_pass informam qual parametro deverá ser passado para ligar o modo debug em servidores que não são de desenvolvimento
 *  deveserá ser usado da seguinte forma:
 *  
 *  www.meusite.com.br/?{$developer_user}={$developer_pass}
 *  
 *  para desligar o debug use:
 *  www.meusite.com.br/?{$developer_user}=off
 *  
 *  O parâmetro \c dba_user habilita o debug de SQLs exibindo TODOS os SQLs executados na página.
 *  Para ligar este modo, primeiro deve-se habilitar o modo desenvolvedor usando o #developer_user#
 *  ex.: www.meusite.com.br/?{$developer_user}={$developer_pass}&{$dba_user}
 *  
 *  Para desligar:
 *  www.meusite.com.br/?{$dba_user}=off
 *  
 *  \see config
 *  @{
 *  @}
 */

/**
 *  \addtogroup systemcfg_default Configurações do cerne para todos os ambientes
 *  \ingroup systemcfg
 *  
 *  As entradas colocadas nesse arquivo serão aplicadas a todos os ambientes do sistema.
 *  
 *  Veja \link systemcfg Configurações do cerne \endlink para entender as entradas de configuração possíveis.
 *  
 *  \see systemcfg
 */
/**@{*/

/// Configurações para todos os ambientes
$conf = array(
	'ignore_deprecated' => false,
	'developer_user'    => '',
	'developer_pass'    => '',
	'dba_user'          => '',
	'assets_path'       => $GLOBALS['SYSTEM']['ROOT_PATH'] . DIRECTORY_SEPARATOR . 'assets',
	'controller_path'   => $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'controllers',
	'css_path'          => $GLOBALS['SYSTEM']['ROOT_PATH'] . DIRECTORY_SEPARATOR . 'css',
	'js_path'           => $GLOBALS['SYSTEM']['ROOT_PATH'] . DIRECTORY_SEPARATOR . 'js',
);

/**@}*/