<?php
/** \file
 *  \brief Configurações gerais do sistema
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 */

/**
 *  \defgroup systemcfg_development Configurações do cerne para o ambiente \c 'development'
 *  \ingroup systemcfg
 *  
 *  As entradas colocadas nesse arquivo serão aplicadas apenas ao ambiente 'development'.
 *  
 *  Seu sistema pode não possuir esse ambiente, então use-o como modelo para criação do arquivo de
 *  parâmetros de configuração para os ambientes que seu sistema possua.
 *  
 *  Veja \link systemcfg Configurações do cerne \endlink para entender as entradas de configuração possíveis.
 *  
 *  \see systemcfg
 */
/**@{*/

/// Configurações para o ambiente de Desenvolvimento
$conf = array(
	'debug'             => true,
	'maintenance'       => false,
	'rewrite_url'       => true,
	'cache'             => false,
	'cache-control'     => 'no-cache',
	'authentication'    => array(),
	'system_error'      => array(
		'save_in_database' => false
	)
);

/**@}*/