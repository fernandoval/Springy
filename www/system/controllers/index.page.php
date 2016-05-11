<?php
/**	\file
 * 	\brief Sample controller for the main page.
 *
 *  \copyright  ₢ 2007-2016 Fernando Val.
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \ingroup    controllers
 */
use \Springy\Controller;

class Index_Controller extends Controller
{
    /**
     *  \brief Método principal (default).
     *
     *  Este método é executado se nenhum outro método for definido na URI para ser chamado, quando essa controladora é chamada.
     */
    public function _default()
    {
        $date = date('F j, Y');

        Springy\Kernel::debug('Exemplo de debug 1');
        Springy\Kernel::debug('Exemplo de debug 2', 'Exemplo com título');
        Springy\Kernel::debug('Exemplo de debug 3', 'Título do Exemplo 3', false, false);

        $tpl = $this->_template();
        $tpl->assign('date', $date);
        $tpl->display();
    }
}
