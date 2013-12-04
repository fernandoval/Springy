<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *	\copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *	\copyright Copyright (c) 2007-2013 Fernando Val\n
 *
 *	\brief		Classe para cliente SOAP
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	1.0.6
 *  \author		Fernando Val  - fernando.val@gmail.com
 *	\ingroup	framework
 */

if (!class_exists('SoapClient')) require_once dirname( __FILE__) . DIRECTORY_SEPARATOR . 'NuSOAP' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'nusoap.php';


class SOAP_Client extends Kernel {
	/// Classe utilizada internamente
	private $classUsed = NULL;
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
		if (class_exists('SoapClient')) {
			$this->classUsed = 'SoapClient';

			if (parent::getConf('system', 'proxyhost')) $options['proxy_host'] = parent::getConf('system', 'proxyhost');
			if (parent::getConf('system', 'proxyport')) $options['proxy_port'] = parent::getConf('system', 'proxyport');
			if (parent::getConf('system', 'proxyusername')) $options['proxy_login'] = parent::getConf('system', 'proxyusername');
			if (parent::getConf('system', 'proxypassword')) $options['proxy_password'] = parent::getConf('system', 'proxypassword');
			if (empty($options['connection_timeout'])) {
				$options['connection_timeout'] = (parent::getConf('soap', 'timeout')) ? parent::getConf('soap', 'timeout') : 20;
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

				$this->client = new SoapClient($endpoint, $options);

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
		}
		else {
			$this->classUsed = 'NuSOAP';
			$this->client = new nusoap_client($endpoint, $wsdl, parent::getConf('soap', 'proxyhost'), parent::getConf('soap', 'proxyport'), parent::getConf('soap', 'proxyusername'), parent::getConf('soap', 'proxypassword'));

			// Pega o erro, caso tenha havido
			if ($this->client->getError()) {
				$this->error = $this->client->getError();
				return false;
			}

			$this->client->useHTTPPersistentConnection();
			$this->client->setUseCurl(parent::getConf('soap', 'useCURL'));
		}

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
		if ($this->classUsed == 'SoapClient') {
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
		}
		else {
			$namespace = isset($options['uri']) ? $options['uri'] : NULL;
			$soapAction = isset($options['soapaction']) ? $options['soapaction'] : NULL;
			$style = isset($options['style']) ? $options['style'] : NULL;
			$use = isset($options['use']) ? $options['use'] : NULL;
			$result = Kernel::arrayToObject($this->client->call($operation, $params, $namespace, $soapAction, $input_headers, $rpcParams, $style, $use));

			if ($this->client->fault) {
				$result = $this->error = "";
				return false;
			} else {
				$err = $this->client->getError();
				if ($err) {
					$result = $this->error = $err;
					return false;
				}
			}
		}
		
		set_time_limit(30);

		return true;
	}

	/**
	 *	\brief Retorna a última requisição
	 */
	public function getLastRequest() {
		if ($this->classUsed == 'SoapClient') {
			return $this->client->__getLastRequest();
		}
		return $this->client->request;
	}

	/**
	 *	\brief Retorna a última resposta
	 */
	public function getLastResponse() {
		if ($this->classUsed == 'SoapClient') {
			return $this->client->__getLastResponse();
		}
		return $this->client->response;
	}

	/**
	 *	\brief Pega o último erro
	 *
	 *	@return Retorna o último erro de execução do método SOAP
	 */
	function getError() {
		return $this->error;
	}

	/**
	 *	\brief Pega o resultado do debug
	 *
	 *	Funciona apenas com NuSOAP
	 */
	function getDebugText() {
		if ($this->classUsed == 'SoapClient') {
			return "";
		}
		return $this->client->getDebug();
	}

	/**
	 *	\brief Define se tenta usar conexão cURL se possível
	 *
	 *	Funciona apenas com NuSOAP
	 *
	 *	@param[in] bool $useCURL: Tenta usar conexão cURL?
	 */
	public function setUseCURL($useCURL) {
		if ($this->classUsed == 'SoapClient') {
			return false;
		}
		return $this->client->setUseCurl($useCURL);
	}

	/**
	 *	\brief Seta o nível de debug
	 *
	 *	Funciona apenas com NuSOAP
	 */
	public function setDebugLevel($level) {
		if ($this->classUsed == 'SoapClient') {
			return false;
		}
		return $this->client->setDebugLevel($level);
	}

	/**
	 *	\brief For creating serializable abstractions of native PHP types.
	 *
	 *	Funciona apenas com NuSOAP
	 *
	 */
	public function soapval($name='soapval', $type, $value=-1, $element_ns=false, $type_ns=false, $attributes=false) {
		if ($this->classUsed == 'SoapClient') {
			return false;
		}
		return new soapval($name, $type, $value, $element_ns, $type_ns, $attributes);
	}

	/**
	 *	\brief Define o encoding.
	 *
	 *	Funciona apenas com NuSOAP
	 *
	 */
	public function setSOAPEncoding($encoding) {
		if ($this->classUsed == 'SoapClient') {
			return false;
		}
		$this->client->soap_defencoding = $encoding;
	}
}

/**
 *	\brief Classe para construção de cabeçalho de autenticação Web Service Security (WSSE)
 */
class WsseAuthHeader extends SoapHeader {
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
		$auth->Username = new SoapVar($user, XSD_STRING, NULL, $this->wss_ns, NULL, $this->wss_ns);
		$auth->Password = new SoapVar($pass, XSD_STRING, NULL, $this->wss_ns, NULL, $this->wss_ns);

		$username_token = new stdClass();
		$username_token->UsernameToken = new SoapVar($auth, SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'UsernameToken', $this->wss_ns);

		$security_sv = new SoapVar(
			new SoapVar($username_token, SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'UsernameToken', $this->wss_ns),
			SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'Security', $this->wss_ns);
		parent::__construct($this->wss_ns, 'Security', $security_sv, true);
	}
}
