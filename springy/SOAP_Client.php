<?php

/**
 * SOAP Client.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @deprecated 4.5
 *
 * @uses Springy\Utils\SoapClient
 *
 * @version   2.1.0
 */

namespace Springy;

use Springy\Utils\SoapClient;

class SOAP_Client
{
    private SoapClient $client;

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
        $awsse = $wsse && isset($options['Username']) && isset($options['Password'])
            ? [
                'username' => $options['Username'],
                'password' => $options['Password'],
            ]
            : [];
        unset($options['Username']);
        unset($options['Password']);

        $this->client = new SoapClient($endpoint, $options, $awsse);
    }

    /**
     * Summary of Call.
     *
     * @param mixed                  $result        by reference will receive the result of the call.
     * @param string                 $operation     SOAP function.
     * @param array|object           $params        arguments to the web service.
     * @param array|null             $options
     * @param \SoapHeader|array|null $input_headers
     *
     * @return bool returns true on success or false if fails.
     */
    public function call(&$result, $operation, $params = [], $options = null, $input_headers = null): bool
    {
        $this->client->call($operation, $params, $options, $input_headers, $output);

        return is_null($this->client->getError());
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
        return is_null($this->client->getError())
            ? ''
            : $this->client->getError()->getCode() . ' - ' . $this->client->getError()->getMessage();
    }
}
