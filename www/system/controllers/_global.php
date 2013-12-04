<?php
/**	\file
 * 	\brief		Controladora global de inicilização da aplicação
 *
 *	Essa classe sempre é construída, independente da controladora chamada.
 *
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.
 *	\ingroup	controllers
 *
 *	\mainpage
 *
 *	<b>FVAL PHP Framework for Web Applications</b>
 *  
 *  Este framework de PHP foi construído com base no conceito MVC, para ser simples e de fácil utilização.
 *  
 *  Modelado para ser leve, rápido e robusto, com ferramentas para facilitar a equipe de desenvolvimento.
 */

class Global_Controller {
	public function __construct() {
		/*
		 *  Como exemplo, variáveis globais de template são inicializadas para entendimento da usabilidade desse hook de controladora
		 */
	
		// Informa para o template se o site está com SSL
		Template_Static::assignDefaultVar('HTTPS',  isset($_SERVER['HTTPS']));
		Template_Static::assignDefaultVar('isMobileDevice', Kernel::getMobileDevice());

		// Inicializa as URLs estáticas
		Template_Static::assignDefaultVar('urlJS',  URI::buildURL(array('js'), array(), true, 'static'));
		Template_Static::assignDefaultVar('urlCSS', URI::buildURL(array('css'), array(), true, 'static'));
		Template_Static::assignDefaultVar('urlIMG', URI::buildURL(array('images'), array(), true, 'static'));
		Template_Static::assignDefaultVar('urlSWF', URI::buildURL(array('swf'), array(), true, 'static'));

		// Inicializa o controle de versões de arquivos estáticos
		Template_Static::registerDefaultPlugin('function', 'files_static_version', 'files_static_version');

		// Inicializa as URLs do site
		Template_Static::assignDefaultVar('urlMain', URI::buildURL(array('')));
		Template_Static::assignDefaultVar('urlLogin', URI::buildURL(array('login'), array(), true, 'secure'));
		Template_Static::assignDefaultVar('urlLogut', URI::buildURL(array('logout'), array(), true, 'secure'));

		// conta o número de parêmetros _GET na URL
		Template_Static::assignDefaultVar('numParamURL',count(URI::getParams()));
		// pegando a URL atual sem paramêtros para passar a tag canonical do google
		Template_Static::assignDefaultVar('urlCurrentURL', URI::buildURL(URI::getAllSegments(), array(), true));
	}
}

/**
 *  \brief Define a versão de arquivos estáticos
 *
 *  Um exemplo de Plugin de template
 */
function files_static_version($params, $smarty) {
	if ($params['type'] == 'js') {
		return URI::buildURL(array('js'), array(), isset($_SERVER['HTTPS']), 'static') . '/' . $params['file'] . '__' . filemtime(Kernel::getConf('system', 'js_path') . DIRECTORY_SEPARATOR . $params['file'] . '.js') . '.js';
	} else {
		return URI::buildURL(array('css'), array(), isset($_SERVER['HTTPS']), 'static') . '/' .$params['file'] . '__' . filemtime(Kernel::getConf('system', 'css_path') . DIRECTORY_SEPARATOR . $params['file'] . '.css') . '.css';
	}
}
