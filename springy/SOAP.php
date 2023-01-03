<?php

/**
 * Class to SOAP connections.
 *
 * This class is not complete. Do not use it.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   0.6.10
 *
 * @deprecated 0.6.10
 */

namespace Springy;

/**
 * SOAP class.
 */
class SOAP
{
    /// Objeto NuSOAP
    private $client = null;
    /// Configuração: endereço do host de proxy
    private $proxyhost = '';
    /// Configuração: porta do proxy
    private $proxyport = '';
    /// Configuração: usuário de acesso ao proxy (caso seja proxy autenticado)
    private $proxyusername = '';
    /// Configuração: senha de acesso ao proxy (caso seja proxy autenticado)
    private $proxypassword = '';

    /**
     * Constructor.
     *
     * @param string $endpoint
     * @param bool   $wsdl
     */
    public function __construct($endpoint = '', $wsdl = false)
    {
        // Pega os dados de proxy da configuração
        $this->proxyhost = Configuration::get('soap', 'proxyhost');
        $this->proxyport = Configuration::get('soap', 'proxyport');
        $this->proxyusername = Configuration::get('soap', 'proxyusername');
        $this->proxypassword = Configuration::get('soap', 'proxypassword');
    }
}
