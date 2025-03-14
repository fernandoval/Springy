<?php

/**
 * Framework copyright class.
 *
 * @copyright 2016 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

namespace Springy\Core;

class Copyright
{
    public function __construct($print = true)
    {
        if ($print) {
            echo file_get_contents(__DIR__ . DS . 'assets' . DS . 'copyright.html');
        }
    }
}
