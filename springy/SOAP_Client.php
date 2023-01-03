<?php

/**
 * SOAP Client.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   2.0.16
 */

namespace Springy;

/**
 * SOAP client class.
 */
class SOAP_Client
{
    /// Objeto SOAP client interno
    private $client = null;
    /// Último erro de execução
    private $error = '';

    /**
     * Constructor.
     *
     * @param string $endpoint web service address.
     * @param bool   $wsdl     if true turns WSDL mode on.
     * @param array  $options  options array.
     * @param bool   $wsse     turns w.s.security on/off.
     */
    public function __construct($endpoint = '', $wsdl = false, $options = [], $wsse = false)
    {
        if (Configuration::get('system', 'proxyhost')) {
            $options['proxy_host'] = Configuration::get('system', 'proxyhost');
        }
        if (Configuration::get('system', 'proxyport')) {
            $options['proxy_port'] = Configuration::get('system', 'proxyport');
        }
        if (Configuration::get('system', 'proxyusername')) {
            $options['proxy_login'] = Configuration::get('system', 'proxyusername');
        }
        if (Configuration::get('system', 'proxypassword')) {
            $options['proxy_password'] = Configuration::get('system', 'proxypassword');
        }
        $options['exceptions'] = true;

        if (empty($options['connection_timeout'])) {
            $options['connection_timeout'] = (Configuration::get('soap', 'timeout')) ? Configuration::get('soap', 'timeout') : 20;
        }
        ini_set('default_socket_timeout', $options['connection_timeout']);

        set_time_limit(0);

        Kernel::addIgnoredError([0, E_WARNING, E_ERROR]);

        try {
            // Monta a autenticação W.S.Security
            if ($wsse && isset($options['Username']) && isset($options['Password'])) {
                $objSoapVarWSSEHeader = new WsseAuthHeader($options['Username'], $options['Password']);
                $options['trace'] = 1;
                $options['exception'] = 0;

                unset($options['Username']);
                unset($options['Password']);
            }

            $this->client = new \SoapClient($endpoint, $options);

            if (isset($objSoapVarWSSEHeader)) {
                $this->client->__setSoapHeaders([$objSoapVarWSSEHeader]);
            }
        } catch (\SoapFault $err) {
            Kernel::delIgnoredError([0, E_WARNING, E_ERROR]);
            $this->error = $err->getMessage();

            return;
        }

        Kernel::delIgnoredError([0, E_WARNING, E_ERROR]);

        set_time_limit(30);
    }

    /**
     * Summary of Call
     *
     * @param mixed                  $result        by reference will receive the result of the call.
     * @param string                 $operation     SOAP function.
     * @param array|Object           $params        arguments to the web service.
     * @param array|null             $options
     * @param \SoapHeader|array|null $input_headers
     *
     * @return bool returns true on success or false if fails.
     */
    public function call(&$result, $operation, $params = [], $options = null, $input_headers = null): bool
    {
        set_time_limit(0);

        $params = [Kernel::objectToArray($params)];

        Kernel::addIgnoredError([0, E_WARNING]);

        try {
            $result = $this->client->__soapCall($operation, $params, $options, $input_headers, $output_headers);
        } catch (\SoapFault $exception) {
            Kernel::delIgnoredError([0, E_WARNING]);
            $result = $this->error = $exception->faultcode . ' - ' . $exception->faultstring;

            return false;
        }

        Kernel::delIgnoredError([0, E_WARNING]);

        set_time_limit(30);

        return true;
    }

    /**
     * Returns last request.
     *
     * @return null|string
     */
    public function getLastRequest()
    {
        return $this->client->__getLastRequest();
    }

    /**
     * Returns last response.
     *
     * @return null|string
     */
    public function getLastResponse()
    {
        return $this->client->__getLastResponse();
    }

    /**
     * Returns last error.
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
}

/**
 *  \brief Classe para construção de cabeçalho de autenticação Web Service Security (WSSE).
 */
class WsseAuthHeader extends \SoapHeader
{
    /// namespace
    private $wss_ns = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

    /**
     * Constructor.
     *
     * @param string $user
     * @param string $pass
     * @param mixed  $ns
     */
    public function __construct($user, $pass, $ns = null)
    {
        if ($ns) {
            $this->wss_ns = $ns;
        }

        $auth = new \stdClass();
        $auth->Username = new \SoapVar($user, \XSD_STRING, null, $this->wss_ns, null, $this->wss_ns);
        $auth->Password = new \SoapVar($pass, \XSD_STRING, null, $this->wss_ns, null, $this->wss_ns);

        $username_token = new \stdClass();
        $username_token->UsernameToken = new \SoapVar($auth, \SOAP_ENC_OBJECT, null, $this->wss_ns, 'UsernameToken', $this->wss_ns);

        $security_sv = new \SoapVar(
            new \SoapVar($username_token, \SOAP_ENC_OBJECT, null, $this->wss_ns, 'UsernameToken', $this->wss_ns),
            \SOAP_ENC_OBJECT,
            null,
            $this->wss_ns,
            'Security',
            $this->wss_ns
        );
        parent::__construct($this->wss_ns, 'Security', $security_sv, true);
    }
}
