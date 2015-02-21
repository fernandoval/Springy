<?php
/** \file
 *  \brief Configurações da classe URI
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 */
/**@{*/

/**
 *  \defgroup uricfg Configurações da classe de tratamento de URI/URL
 *  \ingroup config
 *  
 *  As entradas de configuração dos arquivos \c uri, são utilizadas pela classe URI, sendo que as entradas previamente definidas não podem ser omitidas
 *  sob risco de dano à aplicação.
 *  
 *  Você poderá adicionar entradas personalizadas de sua aplicação.
 *  
 *  Exemplo de código PHP de como usar a entrada \c 'register_method_set_common_urls':
 *  
 *  \code{.php}
 *  $conf = array(
 *      'register_method_set_common_urls' => array(
 *          'class' => 'Url1',
 *          'method' => 'setCommon',
 *          'static' => true
 *      ), // executa: Url1::setCommon()
 *  );
 *  $conf = array(
 *      'register_method_set_common_urls' => array(
 *          'class' => 'Urls',
 *          'method' => 'setCommon',
 *          'static' => false
 *      ), // executa: (new Urls())->setCommon()
 *  );
 *  $conf = array(
 *      'register_method_set_common_urls' => array(
 *          'class' => 'Urls',
 *          'static' => false
 *      ), // executa: new Urls()
 *  );
 *  \endcode
 *
 *  Entradas de configuração:
 *  - 'routes' - Array contendo rotas de controladoras. Útil para utilizar mais de uma URL apontando para mesma controladora.
 *  - 'redirects' - Array de redirecionamentos. Útil para configurar redirecionamentos a acessos que possam causar erro de página não encontrada.
 *  - \c 'prevalidate_controller' - Validação prévia de formatação de URI para determinadas controladoras
 *  - \c 'system_root' - URI da página inicial
 *  - \c 'common_urls' - Essa entrada é um array de URLs comuns da aplicação que irão gerar variáveis de template.\n
 *      Cada entrada do array contém uma variável de template, onde o índice é uma string contendo o nome da variável e o valor
 *      é um array podendo conter de um a todos os parâmetros necessários para construção da URL,
 *      conforme a ordem do método buildURL da classe URI.\n
 *      Logo: cada índice do array conterá a seguinte informação, respectivamente:
 *      índice 0 (zero) => array de segmentos da URL;
 *      índice 1 => array da query string;
 *      índice 2 => boleano para o parâmetro forceRewrite;
 *      índice 3 => entrada de configuração host;
 *      índice 4 => booleano para o parânetro include_ignores_segments. 
 *  - 'redirect_last_slash' - Valor boleano que determina se a aplicação deve redirecionar requisições a URIs terminadas em barra (/) para URI semelhante sem a barra no final.
 *  	Útil para evitar conteúdo duplicado em ferramentas SEO.
 *  - 'force_slash_on_index' - 	Força o uso de barra (/) ao final da URL para o primeiro segmento (index).
 *  	Se a URL acessada for da página principal (index) e não houver a barra ao final, faz o redirecionamento para a URL com / ao final.
 *  	Esse parâmetro invalida 'redirect_last_slash' para a página principal.
 *  - 'ignored_segments' - 	Quantidade de segmentos iniciais que devem ser ignorados na descoberta da controladora.
 *  	Útil para sites onde alguns dos segmentos iniciais servem para algum parâmetro a ser passado para a aplicação, como por exemplo para determinar
 *  	idioma, localização ou setor. Por exemplo: http://www.seusite.com/pt-br/pagina
 *  - 'host_controller_path' - Array de hosts que possuem nível próprio de controladoras.
 *  - 'dynamic' - Host do conteúdo dinâmico do site. Ex.: 'http://www.seusite.com'
 *  - 'static' - Host do conteúdo estático do site. Ex.: 'http://cdn.seusite.com'
 *  - 'secure' - Host seguro do site. Ex.: 'https://www.seusite.com'
 *  
 *  \see config
 *  @{
 *  @}
 */

/**
 *  \defgroup uricfg_default Configurações da classe de tratamento de URI/URL para todos os ambientes
 *  \ingroup uricfg
 *  
 *  As entradas colocadas nesse arquivo serão aplicadas a todos os ambientes do sistema.
 *  
 *  Veja \link uricfg Configurações da classe de tratamento de URI/URL \endlink para entender as entradas de configuração possíveis.
 *  
 *  \see uricfg
 */
/**@{*/

/// Configurações para todos os ambientes
$conf = array(
	'routes' => array(
		'home(\/)*(\?(.*))*' => array('segment' => 0, 'controller' => 'index'),
	),
	'redirects' => array(
		'404' => array('segments' => array(), 'get' => array(), 'force_rewrite' => false, 'host' => 'dynamic', 'type' => 301),
	),
	'prevalidate_controller' => array(
		'mycontroller' => array('command' => 301, 'segments' => 2),
		'myothercontroller' => array('command' => 404, 'segments' => 2, 'validate' => array('/^[a-z0-9\-]+$/', '/^[0-9]+$/')),
	),
	'system_root'                     => '/',
	'register_method_set_common_urls' => null,
	// URLs comuns do site
	'common_urls'                     => array(
		'urlAssets' => array(array('assets'), array(), false, 'static', true),
		'urlCSS' => array(array('assets', 'css'), array(), false, 'static', true),
		'urlJS' => array(array('assets', 'js'), array(), false, 'static', true),
		'urlHome' => array(array()),
		'urlLogin' => array(array('login'), array(), false, 'secure', true),
		'urlLogout' => array(array('logout'), array(), false, 'secure', true)
	),
	'redirect_last_slash'             => true,
	'force_slash_on_index'            => true,
	'ignored_segments'                => 0,
	'assets_dir'                      => 'assets',
	'js_dir'                          => 'js',
	'css_dir'                         => 'css',
	'images_dir'                      => 'images',
	'swf_dir'                         => 'swf'
);

/**@}*/