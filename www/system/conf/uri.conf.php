<?php
/** \file
 *  \brief Configurações da classe URI
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *  \addtogroup	config
 */
/**@{*/

/**
 *  \addtogroup uricfg Configurações da classe de tratamento de URI/URL
 *  How to use: [pt-br] : Exemplo de código PHP de como usar
 *
 *	$conf['default']['register_method_set_common_urls'] = array('class' => 'Urls', 'method' => 'setCommon', 'static' => true) // executa: Urls::setCommon();
 *	$conf['default']['register_method_set_common_urls'] = array('class' => 'Urls', 'method' => 'setCommon', 'static' => false) // executa: (new Urls())->setCommon();
 *	$conf['default']['register_method_set_common_urls'] = array('class' => 'Urls', 'static' => false) // executa: new Urls();
 *
 *  Entradas de configuração:
 *  - 'routes' - Attay contendo rotas de controladoras. Útil para utilizar mais de uma URL apontando para mesma controladora.
 *  - 'redirects' - Array de redirecionamentos. Útil para configurar redirecionamentos a acessos que possam causar erro de página não encontrada.
 *  - 'redirect_last_slash' - Valor boleano que determina se a aplicação deve redirecionar requisições a URIs terminadas em barra (/) para URI semelhante sem a barra no final.
 *  	Útil para evitar conteúdo duplicado em ferramentas SEO.
 *  - 'force_slash_on_index' - 	Força o uso de barra (/) ao final da URL para o primeiro segmento (index).
 *  	Se a URL acessada for da página principal (index) e não houver a barra ao final, faz o redirecionamento para a URL com / ao final.
 *  	Esse parâmetro invalida 'redirect_last_slash' para a página principal.
 *  - 'ignored_segments' - 	Quantidade de segmentos iniciais que devem ser ignorados na descoberta da controladora.
 *  	Útil para sites onde alguns dos segmentos iniciais servem para algum parãmetro a ser passado para a aplicação, como por exemplo para determinar
 *  	idioma, localização ou setor. Por exemplo: http://www.seusite.com/pt-br/pagina
 *  - 'host_controller_path' - Array de hosts que possuem nível próprio de controladoras.
 *  - 'dynamic' - Host do conteúdo dinãmico do site. Ex.: 'http://www.seusite.com'
 *  - 'static' - Host do conteúdo estático do site. Ex.: 'http://cdn.seusite.com'
 *  - 'secure' - Host segudo do site. Ex.: 'https://www.seusite.com'
 */
/**@{*/

/**
 *  @name Configurações para todos os ambientes
 */
///@{
/// Rotas alternativas para controladoras
$conf['default']['routes'] = array(
	'home(\/)*(\?(.*))*' => array('segment' => 0, 'controller' => 'index'),
);

/// Redirecionamentos
$conf['default']['redirects'] = array(
	'404' => array('segments' => array(), 'get' => array(), 'force_rewrite' => false, 'host' => 'dynamic', 'type' => 301),
);

/// Validação prévia de formatação de URI para determinadas controladoras
$conf['default']['prevalidate_controller'] = array(
	'mycontroller' => array('command' => 301, 'segments' => 2),
	'myothercontroller' => array('command' => 404, 'segments' => 2, 'validate' => array('/^[a-z0-9\-]+$/', '/^[0-9]+$/')),
);

/// URI da aplicação
$conf['default']['system_root'] = '/';

$conf['default']['register_method_set_common_urls'] = NULL;
$conf['default']['common_urls'] = array();
/// Remove a última barra (/) e redireciona para URL sem com objetivo de evitar conteúdo duplicado
$conf['default']['redirect_last_slash'] = true;
/// Força o uso de barra (/) ao final da URL para o primeiro segmento (index).
$conf['default']['force_slash_on_index'] = true;
/// Número de segmentos ignorados no parse da URI para determinação da controladora
$conf['default']['ignored_segments'] = 1;
///@}

/**
 *  @name Configurações para o ambiente de Desenvolvimento
 */
///@{
/// Root controllers path por HOST
$conf['development']['host_controller_path'] = array(
	'host.seusite.localhost' => array('diretorio'),
);

$conf['development']['dynamic'] = $_SERVER['HTTP_HOST'];
$conf['development']['static'] = $_SERVER['HTTP_HOST'];
$conf['development']['secure'] = $_SERVER['HTTP_HOST'];
///@}

/**
 *  @name Configurações para o ambiente de Produção
 */
///@{
/// Root controllers path por HOST
$conf['production']['host_controller_path'] = array(
	'host.seusite.com' => array('diretorio'),
);

$conf['production']['dynamic'] = $_SERVER['HTTP_HOST'];
$conf['production']['static'] = $_SERVER['HTTP_HOST'];
$conf['production']['secure'] = $_SERVER['HTTP_HOST'];
///@}

/**@}*/
/**@}*/
