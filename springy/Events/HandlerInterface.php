<?php

/**
 * Event handlers inferface
 *
 * @copyright 2007 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   0.1.2
 */

namespace Springy\Events;

interface HandlerInterface
{
    /**
     * Registra esta classe como handlers nos evetnos necessários.
     */
    public function subscribes(Mediator $mediator);
}
