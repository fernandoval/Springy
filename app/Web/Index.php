<?php

/**
 * Sample controller for the main page.
 */

namespace App\Web;

use Springy\Controller;

class Index extends Controller
{
    public function __invoke()
    {
        $date = date('F j, Y');

        debug('Exemplo de debug 1');
        debug('Exemplo de debug 2', 'Exemplo com título');
        debug('Exemplo de debug 3', 'Título do Exemplo 3', false, false);

        $this->createTemplate();
        $this->template->assign('date', $date);
        $this->template->display();
    }
}
