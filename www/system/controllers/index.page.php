<?php
/**	\file
 * 	\brief		Controladora da página principal da aplicação
 *
 *	\ingroup	controllers
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *  \author		Fernando Val  - fernando.val@gmail.com
 */

class Index_Controller {
	/**
	 *  \brief Método construtor da controladora.
	 *
	 *  Este método sempre é executado quando a controladora é criada.
	 *  Tudo que precisa se inicializadou e/ou instanciado com a conroladora deve ser colocado nesse método.
	 */
	function __construct() {
		date_default_timezone_set('America/Sao_Paulo');
	}

	/**
	 *  \brief Método principal (default)
	 *
	 *  Este método é executado se nenhum outro método for definido na URI para ser chamado, quando essa controladora é chamada.
	 */
	function _default() {
		$date = date('F j, Y');
		
		FW\Kernel::debug('Exemplo de debug 1');
		FW\Kernel::debug('Exemplo de debug 2', 'Exemplo com título');
		FW\Kernel::debug('Exemplo de debug 3', 'Título do Exemplo 3', false, false);

		$tpl = new FW\Template;
		$tpl->assign('date', $date);
		$tpl->display();
	}
}