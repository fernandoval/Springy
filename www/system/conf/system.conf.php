<?php
/** \file
 *  \brief Configurações gerais do sistema
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *  \addtogroup	config
 */
/**@{*/

/**
 *  \addtogroup systemcfg Configurações do cerne
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
 *  Entradas de configuração:
 *  - \c controller_path - Caminho do diretório de scripts de controle (controllers)
 *  - \c debug - Define se debug está ativo
 *  - \c maintenance - Define se está em manutenção
 *  - \c admin_maintenance - Define se o ADMIN está em manutenção
 *  - \c rewrite_url - Define se rewrite de URL está ativo
 *  - \c cache - Define se o cache está ligado
 *  - \c cache-control - Define o header HTTP/1.1 Cache-Control
 *  - \c authentication - Define o acesso autenticado por HTTP
 *    - Esse parâmetro de configuração espera um valor \c false ou um \c array no seguinte formato:
 *      array('user' => 'usuario', 'pass' => 'senha')
 */
/*@{*/

/// Configurações para todos os ambientes
$conf['default'] = array(
	'developer_user'  => '',
	'developer_pass'  => '',
	'dba_user'        => '',
	'controller_path' => $GLOBALS['SYSTEM']['SYSTEM_PATH'] . DIRECTORY_SEPARATOR . 'controllers'
);

/// Configurações para o ambiente de Desenvolvimento
$conf['development'] = array(
	'debug'             => true,
	'maintenance'       => false,
	'admin_maintenance' => false,
	'rewrite_url'       => true,
	'cache'             => false,
	'cache-control'     => 'no-cache',
	'authentication'    => array()
);

/// Configurações para o ambiente de Produção
$conf['production'] = array(
	'debug'             => false,
	'maintenance'       => false,
	'admin_maintenance' => false,
	'rewrite_url'       => true,
	'cache'             => false,
	'cache-control'     => 'private, must-revalidate',
	'authentication'    => false
);

/**@}*/
/**@}*/
