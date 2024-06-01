<?php

/**
 * SOAP Client.
 *
 * @copyright 2024 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version 1.0.0
 */

namespace Springy\Utils;

use Exception;
use SoapClient as GlobalSoapClient;
use SoapFault;
use SoapHeader;
use Springy\Configuration;
use Springy\Exceptions\SpringyException;
use Springy\Kernel;

class SoapClient extends GlobalSoapClient
{
    private SpringyException|null $error;

    public function __construct(?string $wsdl, array $options, array $wsse)
    {
        $options['exceptions'] = $options['exceptions'] ?? true;
        $this->initProxy($options);
        $this->initTimeout($options);
        $this->initWsse($wsse, $options);

        $met = ini_get('max_execution_time');
        set_time_limit(0);
        Kernel::addIgnoredError([0, E_WARNING, E_ERROR]);

        try {
            parent::__construct($wsdl, $options);
            $this->setWsse($wsse, $options);
            $this->error = null;
        } catch (SoapFault $error) {
            $this->error = new SpringyException(
                $error->faultcode . ' - ' . $error->faultstring,
                E_ERROR,
                $error,
                $error->getFile(),
                $error->getLine()
            );
        } catch (Exception $error) {
            $this->error = new SpringyException(
                $error->getMessage(),
                $error->getCode(),
                $error,
                $error->getFile(),
                $error->getLine()
            );
        }

        Kernel::delIgnoredError([0, E_WARNING, E_ERROR]);
        set_time_limit($met);
    }

    /**
     * Calls the SOAP function.
     *
     * @param string                     $name
     * @param array                      $args
     * @param array|null                 $options
     * @param SoapHeader|array|null|null $inputHeaders
     * @param array|null                 $outputHeaders
     *
     * @return mixed
     */
    public function call(
        string $name,
        array $args,
        ?array $options = null,
        SoapHeader|array|null $inputHeaders = null,
        array &$outputHeaders = null
    ): mixed {
        $result = false;
        $met = ini_get('max_execution_time');
        Kernel::addIgnoredError([0, E_WARNING]);

        try {
            $result = $this->__soapCall($name, $args, $options, $inputHeaders, $outputHeaders);
            $this->error = null;
        } catch (SoapFault $error) {
            $this->error = new SpringyException(
                $error->faultcode . ' - ' . $error->faultstring,
                E_ERROR,
                $error,
                $error->getFile(),
                $error->getLine()
            );
        }

        Kernel::delIgnoredError([0, E_WARNING]);
        set_time_limit($met);

        return $result;
    }

    /**
     * Returns last error or null.
     *
     * @return SpringyException|null
     */
    public function getError(): ?SpringyException
    {
        return $this->error;
    }

    private function initProxy(array &$options): void
    {
        $host = Configuration::get('network.soap.proxy.host') ?? Configuration::get('system.proxyhost');
        $port = Configuration::get('network.soap.proxy.port') ?? Configuration::get('system.proxyport');
        $user = Configuration::get('network.soap.proxy.user') ?? Configuration::get('system.proxyusername');
        $pass = Configuration::get('network.soap.proxy.password') ?? Configuration::get('system.proxypassword');

        if (!isset($options['proxy_host']) && $host) {
            $options['proxy_host'] = $host;
        }
        if (!isset($options['proxy_port']) && $port) {
            $options['proxy_port'] = $port;
        }
        if (!isset($options['proxy_login']) && $user) {
            $options['proxy_login'] = $user;
        }
        if (!isset($options['proxy_password']) && $pass) {
            $options['proxy_password'] = $pass;
        }
    }

    private function initTimeout(array &$options): void
    {
        if (empty($options['connection_timeout'])) {
            $options['connection_timeout'] = Configuration::get('network.soap.timeout')
                ?? Configuration::get('soap.timeout') ?: 20;
        }

        ini_set('default_socket_timeout', $options['connection_timeout']);
    }

    private function initWsse(array $wsse, array &$options): void
    {
        if ($this->isUsingWsse($wsse)) {
            $options['trace'] = true;
        }
    }

    private function isUsingWsse(array $wsse): bool
    {
        return count($wsse) && isset($wsse['username']) && isset($wsse['password']);
    }

    private function setWsse(array $wsse): void
    {
        if ($this->isUsingWsse($wsse)) {
            $this->__setSoapHeaders([
                new WsseAuthHeader(
                    $wsse['username'],
                    $wsse['password'],
                    null
                ),
            ]);
        }
    }
}
