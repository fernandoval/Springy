<?php
/*  ------------------------------------------------------------------------------------ --- -- -
	FVAL PHP Framework for Web Sites

	Copyright (C) 2009 FVAL - Consultoria e Informсtica Ltda.
	Copyright (C) 2009 Fernando Val
	Copyright (C) 2009 Lucas Cardozo

	http://www.fval.com.br

	Developer team:
		Fernando Val  - fernando.val@gmail.com
		Lucas Cardozo - lucas.cardozo@gmail.com

	Framework version:
		1.0.0

	Script version:
		1.0.0

	This script:
		System configurations
	------------------------------------------------------------------------------------ --- -- - */

/* *************** CONFIGURAЧеES DE DESENVOLVIMENTO *************** */

// [pt-br] Define se ambiente щ desenvolvimento e debug estс ativo
$conf['development']['development'] = true;
$conf['development']['debug'] = true;

// [pt-br] Configuraчѕes do ambiente
$conf['development']['rewrite_url'] = true;

// [pt-br] Caminhos
$conf['development']['root_path'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..';
$conf['development']['controller_path'] = $conf['development']['root_path'] . DIRECTORY_SEPARATOR . 'pages';
$conf['development']['template_path'] = $conf['development']['root_path'] . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'default';
$conf['development']['compiled_template_path'] = $conf['development']['root_path'] . DIRECTORY_SEPARATOR . 'tpl_c';

$conf['development']['pathWWW'] = $conf['development']['root_path'] . DIRECTORY_SEPARATOR . 'www';

/* *** SISTEMA *** */
$conf['development']['uri'] = 'yoursitedomain';
$conf['development']['cache'] = false;

/*
	coloca uma extensуo na url em modo re-write
	ex: http://www.meusite.com.br/pagina/parametro.html
*/
$conf['development']['ext_file_url'] = '';

/* *** dados *** */
$conf['development']['siteName'] = 'iMuzDB';
$conf['development']['searchItensPerPage'] = 15;
$conf['development']['viewAllArtistsItensPerPage'] = 45;
$conf['development']['viewAllAlbumsItensPerPage'] = 36;
$conf['development']['viewAllSongsItensPerPage'] = 45;
$conf['development']['viewAllGenresItensPerPage'] = 45;
$conf['development']['viewAllCategoriesItensPerPage'] = 45;
$conf['development']['maxAgeCacheLyrics'] = 30;

/* *************** CONFIGURAЧеES DE PRODUCAO *************** */

// [pt-br] Define se ambiente щ desenvolvimento e debug estс ativo
$conf['production']['development'] = false;
$conf['production']['debug'] = false;

// [pt-br] Configuraчѕes do ambiente
$conf['production']['rewrite_url'] = true;

// [pt-br] Caminhos
$conf['production']['root_path'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..';
$conf['production']['controller_path'] = $conf['production']['root_path'] . DIRECTORY_SEPARATOR . 'pages';
$conf['production']['template_path'] = $conf['production']['root_path'] . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'default';
$conf['production']['template_config_path'] = $conf['production']['root_path'] . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'default';
$conf['production']['compiled_template_path'] = $conf['production']['root_path'] . DIRECTORY_SEPARATOR . 'tpl_c';

$conf['production']['pathWWW'] = $conf['production']['root_path'] . DIRECTORY_SEPARATOR . 'www';

/* *** SISTEMA *** */
$conf['production']['uri'] = 'yoursitedomain';
$conf['production']['cache'] = true;

/*
	coloca uma extensуo na url em modo re-write
	ex: http://www.meusite.com.br/pagina/parametro.html
*/
$conf['production']['ext_file_url'] = '';

/* *** dados *** */
$conf['production']['siteName'] = 'iMuzDB';
$conf['production']['searchItensPerPage'] = 15;
$conf['production']['viewAllArtistsItensPerPage'] = 45;
$conf['production']['viewAllAlbumsItensPerPage'] = 36;
$conf['production']['viewAllSongsItensPerPage'] = 45;
$conf['production']['viewAllGenresItensPerPage'] = 45;
$conf['production']['viewAllCategoriesItensPerPage'] = 45;
$conf['production']['maxAgeCacheLyrics'] = 30;
?>