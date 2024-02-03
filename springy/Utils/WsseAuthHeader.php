<?php

/**
 * WSSE Authentitation Header for SoapClient.
 *
 * @copyright 2024 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version 1.0.0
 */

namespace Springy\Utils;

use SoapHeader;
use SoapVar;

class WsseAuthHeader extends SoapHeader
{
    public function __construct(string $username, string $password, ?string $nodeNamespace)
    {
        $namespace = $nodeNamespace
            ?? 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

        parent::__construct(
            $namespace,
            'Security',
            new SoapVar(
                new SoapVar(
                    (object) [
                        'UsernameToken' => new SoapVar(
                            (object) [
                                'Username' => new SoapVar(
                                    $username,
                                    XSD_STRING,
                                    null,
                                    $namespace,
                                    null,
                                    $namespace
                                ),
                                'Password' => new SoapVar(
                                    $password,
                                    XSD_STRING,
                                    null,
                                    $namespace,
                                    null,
                                    $namespace
                                ),
                            ],
                            SOAP_ENC_OBJECT,
                            null,
                            $namespace,
                            'UsernameToken',
                            $namespace
                        ),
                    ],
                    SOAP_ENC_OBJECT,
                    null,
                    $namespace,
                    'UsernameToken',
                    $namespace
                ),
                SOAP_ENC_OBJECT,
                null,
                $namespace,
                'Security',
                $namespace
            ),
            true
        );
    }
}
