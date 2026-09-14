<?php

/**
 * SOAP Client.
 *
 * phpcs:disable Squiz.Classes.ValidClassName.NotPascalCase
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @deprecated 4.5
 *
 * @uses Springy\Utils\SoapClient
 */

namespace Springy;

use Exception;
use Springy\Utils\SoapClient;

class SOAP_Client
{
    public function __construct($endpoint = '', $wsdl = false, $options = [], $wsse = false)
    {
        throw new Exception(
            'SOAP_Client is deprecated since version 4.5. Use Springy\Utils\SoapClient instead.',
            E_USER_DEPRECATED
        );
    }
}
