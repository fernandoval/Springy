<?php
/** \file
 *  \brief Configurações da classe URI
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 */

/**
 *  \defgroup uricfg_development Configurações da classe de tratamento de URI/URL para o ambiente 'development'
 *  \ingroup uricfg
 *  
 *  As entradas colocadas nesse arquivo serão aplicadas apenas ao ambiente 'development'.
 *  
 *  Seu sistema pode não possuir esse ambiente, então use-o como modelo para criação do arquivo de
 *  parâmetros de configuração para os ambientes que seu sistema possua.
 *  
 *  Veja \link uricfg Configurações da classe de tratamento de URI/URL \endlink para entender as entradas de configuração possíveis.
 *  
 *  \see uricfg
 */
/**@{*/

/// Configurações para o ambiente de Desenvolvimento
$conf = array(
	'host_controller_path' => array(
		'host.seusite.localhost' => array('diretorio'),
	),
	'dynamic' => $_SERVER['HTTP_HOST'],
	'static'  => $_SERVER['HTTP_HOST'],
	'secure'  => $_SERVER['HTTP_HOST']
);

/// Configurações sobrescritas para hosts específicos (EXEMPLO)
$over_conf = array(
	'host.seusite.localhost' => array (
		'dynamic'          => 'http://host.seusite.localhost',
		'prevalidate_controller' => array(
			'mycontroller' => array('command' => 404, 'segments' => 2, 'validate' => array('/^[a-z0-9\-]+$/', '/^[0-9]+$/')),
		),
	)
);

/**@}*/