<?php
/**
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2011 FVAL Consultoria e Informática Ltda.\n
 *	Copyright (c) 2007-2011 Fernando Val
 *
 *	\warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\version 0.3.4
 *
 *	\brief Classe para conexão SOAP
 */

require_once dirname( __FILE__) . DIRECTORY_SEPARATOR . 'NuSOAP' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'nusoap.php';

class SOAP {
	/// Objeto NuSOAP
	private $client = NULL;
	/// Configuração: endereço do host de proxy
	private $proxyhost = '';
	/// Configuração: porta do proxy
	private $proxyport = '';
	/// Configuração: usuário de acesso ao proxy (caso seja proxy autenticado)
	private $proxyusername = '';
	/// Configuração: senha de acesso ao proxy (caso seja proxy autenticado)
	private $proxypassword = '';

	/**
	 *	\brief Construtor da classe agregadora
	 */
	function __construct($endpoint='', $wsdl=false) {
		// Pega os dados de proxy da configuração
		$this->proxyhost     = Configuration::get('soap', 'proxyhost');
		$this->proxyport     = Configuration::get('soap', 'proxyport');
		$this->proxyusername = Configuration::get('soap', 'proxyusername');
		$this->proxypassword = Configuration::get('soap', 'proxypassword');
	}

	/**
	 *	\brief Construtor da classe cliente
	 */
	function clientCreate($endpoint='', $wsdl=false) {
		// Cria o cliente de SOAP
		$this->client = new nusoap_client($endpoint, $wsdl, $this->proxyhost, $this->proxyport, $this->proxyusername, $this->proxypassword);

		// Pega o erro, caso tenha havido
		$err = $this->client->getError();

		if ($err) {
			return false;
		}

		$this->client->useHTTPPersistentConnection();

		return true;
	}

	/**
	 *	\brief Pega o último erro
	 */
	function getClientError() {
		return $this->client->getError();
	}

	/**
	 *	\brief Pega o resultado do debug
	 */
	function getClientDebug() {
		return $this->client->getDebug();
	}

	/**
	 *	\brief Faz uma chamada SOAP
	 */
	public function clientCall(&$result, $operation, $params=array(), $namespace='http://tempuri.org', $soapAction='', $headers=false, $rpcParams=NULL, $style='rpc', $use='encoded') {
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
	 *	\brief Define se tenta usar conexão cURL se possível
	 *	@param[in] bool $useCURL: Tenta usar conexão cURL?
	 */
	public function setClientUseCurl($useCURL) {
		$this->client->setUseCurl($useCURL);
	}

	/**
	 *	\brief For creating serializable abstractions of native PHP types.
	 */
	public function soapval($name='soapval', $type, $value=-1, $element_ns=false, $type_ns=false, $attributes=false) {
		return new soapval($name, $type, $value, $element_ns, $type_ns, $attributes);
	}
	
	/**
	 *	\brief Define o encoding.
	 */
	public function setSOAPEncoding($encoding) {
		$this->client->soap_defencoding = $encoding;
	}
}
