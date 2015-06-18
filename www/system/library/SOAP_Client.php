<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright	Copyright (c) 2007-2015 Fernando Val\n
 *
 *	\brief		Classe para cliente SOAP
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	2.0.11
 *  \author		Fernando Val  - fernando.val@gmail.com
 *	\ingroup	framework
 */

namespace FW;

/**
 *  \brief Classe para cliente SOAP
 */
class SOAP_Client {
	/// Objeto SOAP client interno
	private $client = NULL;
	/// Último erro de execução
	private $error = "";

	/**
	 *	\brief Construtor da classe
	 *
	 *	@param[in] (string) $endpoint - endereço URI do web service
	 *	@param[in] (bool) $wsdl - define o modo de chamada como WSDL
	 *	@param[in] (array) $options - array de opções
	 */
	public function __construct($endpoint='', $wsdl=false, $options=array(), $wsse=false) {
		if (Configuration::get('system', 'proxyhost')) $options['proxy_host'] = Configuration::get('system', 'proxyhost');
		if (Configuration::get('system', 'proxyport')) $options['proxy_port'] = Configuration::get('system', 'proxyport');
		if (Configuration::get('system', 'proxyusername')) $options['proxy_login'] = Configuration::get('system', 'proxyusername');
		if (Configuration::get('system', 'proxypassword')) $options['proxy_password'] = Configuration::get('system', 'proxypassword');
		if (empty($options['connection_timeout'])) {
			$options['connection_timeout'] = (Configuration::get('soap', 'timeout')) ? Configuration::get('soap', 'timeout') : 20;
		}
		ini_set('default_socket_timeout', $options['connection_timeout']);

		restore_error_handler();
		
		set_time_limit(0);

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
				$this->client->__setSoapHeaders(array($objSoapVarWSSEHeader));
			}
		}
		catch (Exception $e) {
			$this->error = $e->getMessage();
			return false;
		}

		set_time_limit(30);
		set_error_handler('FW_ErrorHandler');

		return true;
	}

	/**
	 *	\brief Faz uma chamada ao web service
	 *
	 *	@param[out] (mixed) &$result - variável passada por referência que receberá o retorno da chamada ao método SOAP
	 *	@param[in] (string) $operation - nome da função SOAP
	 *	@param[in] (array|stdClass) $params - parâmatros a serem passados para o web service
	 *	@result (boolean) Retornará true se a chamada for bem sucedida ou false se houver um erro de conexão com o serviço
	 */
	public function call(&$result, $operation, $params=array(), $options=NULL, $input_headers=NULL) {
		set_time_limit(0);

		// $params = $this->_arrayToObject($params);
		$params = array(Kernel::objectToArray($params));

		restore_error_handler();

		try {
			// $result = $this->client->$operation($params);
			$result = $this->client->__soapCall($operation, $params, $options, $input_headers, $output_headers);
		}
		catch(SoapFault $exception) {
			$result = $this->error = $exception->faultcode.' - '.$exception->faultstring;
			return false;
		}

		set_error_handler('FW_ErrorHandler');
		set_time_limit(30);

		return true;
	}

	/**
	 *	\brief Retorna a última requisição
	 */
	public function getLastRequest() {
		return $this->client->__getLastRequest();
	}

	/**
	 *	\brief Retorna a última resposta
	 */
	public function getLastResponse() {
		return $this->client->__getLastResponse();
	}

	/**
	 *	\brief Pega o último erro
	 *
	 *	@return Retorna o último erro de execução do método SOAP
	 */
	function getError() {
		return $this->error;
	}
}

/**
 *	\brief Classe para construção de cabeçalho de autenticação Web Service Security (WSSE)
 */
class WsseAuthHeader extends \SoapHeader {
	/// namespace
	private $wss_ns = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

	/**
	 *	\brief Método construtur
	 *
	 *	@param[in] (string) $user - username
	 *	@param[in] (string) $pass - password
	 *	@param[in] (string) $ns - namespace (optional)
	 */
	function __construct($user, $pass, $ns = null) {
		if ($ns) {
			$this->wss_ns = $ns;
		}

		$auth = new stdClass();
		$auth->Username = new \SoapVar($user, \XSD_STRING, NULL, $this->wss_ns, NULL, $this->wss_ns);
		$auth->Password = new \SoapVar($pass, \XSD_STRING, NULL, $this->wss_ns, NULL, $this->wss_ns);

		$username_token = new stdClass();
		$username_token->UsernameToken = new \SoapVar($auth, \SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'UsernameToken', $this->wss_ns);

		$security_sv = new \SoapVar(
			new \SoapVar($username_token, \SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'UsernameToken', $this->wss_ns),
			\SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'Security', $this->wss_ns);
		parent::__construct($this->wss_ns, 'Security', $security_sv, true);
	}
}
