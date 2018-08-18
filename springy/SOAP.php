<?php
/** \file
 *  Springy.
 *
 *	\brief		Classe para conexão SOAP
 *  \copyright	Copyright (c) 2007-2016 Fernando Val
 *  \author		Fernando Val  - fernando.val@gmail.com
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	0.6.9
 *  \ingroup	framework
 *
 *  This class is not terminated and is experimental. Do not use.
 */

namespace Springy;

/**
 *  \brief Classe para conexão SOAP.
 *
 *  \warning Esta classe ainda está sendo desenvolvida e é experimental. Não a utilize.
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
     *	\brief Construtor da classe agregadora.
     */
    public function __construct($endpoint = '', $wsdl = false)
    {
        // Pega os dados de proxy da configuração
        $this->proxyhost = Configuration::get('soap', 'proxyhost');
        $this->proxyport = Configuration::get('soap', 'proxyport');
        $this->proxyusername = Configuration::get('soap', 'proxyusername');
        $this->proxypassword = Configuration::get('soap', 'proxypassword');
    }

    /**
     *	\brief Construtor da classe cliente.
     */
    public function clientCreate($endpoint = '', $wsdl = false)
    {
        // Cria o cliente de SOAP
        $this->client = new \nusoap_client($endpoint, $wsdl, $this->proxyhost, $this->proxyport, $this->proxyusername, $this->proxypassword);

        // Pega o erro, caso tenha havido
        $err = $this->client->getError();

        if ($err) {
            return false;
        }

        $this->client->useHTTPPersistentConnection();

        return true;
    }

    /**
     *	\brief Pega o último erro.
     */
    public function getClientError()
    {
        return $this->client->getError();
    }

    /**
     *	\brief Pega o resultado do debug.
     */
    public function getClientDebug()
    {
        return $this->client->getDebug();
    }

    /**
     *	\brief Faz uma chamada SOAP.
     */
    public function clientCall(&$result, $operation, $params = [], $namespace = 'http://tempuri.org', $soapAction = '', $headers = false, $rpcParams = null, $style = 'rpc', $use = 'encoded')
    {
        $result = $this->client->call($operation, $params, $namespace, $soapAction, $headers, $rpcParams, $style, $use);
        if ($this->client->fault) {
            return false;
        } else {
            $err = $this->client->getError();
            if ($err) {
                $result = $err;

                return false;
            }
        }

        return true;
    }

    /**
     *	\brief Define se tenta usar conexão cURL se possível.
     *
     *	@param[in] bool $useCURL: Tenta usar conexão cURL?
     */
    public function setClientUseCurl($useCURL)
    {
        $this->client->setUseCurl($useCURL);
    }

    /**
     *	\brief For creating serializable abstractions of native PHP types.
     */
    public function soapval($name, $type, $value = -1, $element_ns = false, $type_ns = false, $attributes = false)
    {
        return new \soapval($name, $type, $value, $element_ns, $type_ns, $attributes);
    }

    /**
     *	\brief Define o encoding.
     */
    public function setSOAPEncoding($encoding)
    {
        $this->client->soap_defencoding = $encoding;
    }
}
