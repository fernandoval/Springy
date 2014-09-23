<?php
/**	\file
 *  \brief		Controladora global de inicilização da aplicação
 *
 *	Essa classe sempre é construída, independente da controladora chamada.
 *
 *	\ingroup	controllers
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.
 *  \author		Fernando Val  - fernando.val@gmail.com
 */

class Global_Controller {
	public function __construct() {
		//  Como exemplo, variáveis globais de template são inicializadas para entendimento da usabilidade desse hook de controladora
	
		// Informa para o template se o site está com SSL
		FW\Template_Static::assignDefaultVar('HTTPS',  isset($_SERVER['HTTPS']));
		FW\Template_Static::assignDefaultVar('isMobileDevice', FW\Browser\OS::isMobile());

		// Inicializa as URLs estáticas
		FW\Template_Static::assignDefaultVar('urlJS',  FW\URI::buildURL(array(FW\Configuration::get('uri', 'js_dir')), array(), true, 'static'));
		FW\Template_Static::assignDefaultVar('urlCSS', FW\URI::buildURL(array(FW\Configuration::get('uri', 'css_dir')), array(), true, 'static'));
		FW\Template_Static::assignDefaultVar('urlIMG', FW\URI::buildURL(array(FW\Configuration::get('uri', 'images_dir')), array(), true, 'static'));
		FW\Template_Static::assignDefaultVar('urlSWF', FW\URI::buildURL(array(FW\Configuration::get('uri', 'swf_dir')), array(), true, 'static'));

		// Inicializa o controle de versões de arquivos estáticos
		FW\Template_Static::registerDefaultPlugin('function', 'files_static_version', 'files_static_version');

		// Inicializa as URLs do site
		FW\Template_Static::assignDefaultVar('urlMain', FW\URI::buildURL(array('')));
		FW\Template_Static::assignDefaultVar('urlLogin', FW\URI::buildURL(array('login'), array(), true, 'secure'));
		FW\Template_Static::assignDefaultVar('urlLogut', FW\URI::buildURL(array('logout'), array(), true, 'secure'));

		// conta o número de parêmetros _GET na URL
		FW\Template_Static::assignDefaultVar('numParamURL',count(FW\URI::getParams()));
		// pegando a URL atual sem paramêtros para passar a tag canonical do google
		FW\Template_Static::assignDefaultVar('urlCurrentURL', FW\URI::buildURL(FW\URI::getAllSegments(), array(), true));
	}
}

/**
 *  \brief Define a versão de arquivos estáticos
 *
 *  Um exemplo de Plugin de template
 */
function files_static_version($params, $smarty) {
	if ($params['type'] == 'js') {
		return FW\URI::buildURL(array('js'), array(), isset($_SERVER['HTTPS']), 'static', false) . '/' . $params['file'] . '__' . filemtime(FW\Configuration::get('system', 'js_path') . DIRECTORY_SEPARATOR . $params['file'] . '.js') . '.js';
	} else {
		return FW\URI::buildURL(array('css'), array(), isset($_SERVER['HTTPS']), 'static', false) . '/' .$params['file'] . '__' . filemtime(FW\Configuration::get('system', 'css_path') . DIRECTORY_SEPARATOR . $params['file'] . '.css') . '.css';
	}
}
