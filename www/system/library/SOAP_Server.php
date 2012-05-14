<?php
/**
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2012 FVAL Consultoria e Informática Ltda.\n
 *	Copyright (c) 2007-2012 Fernando Val
 *
 *	\warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\version 0.1.0
 *
 *	\brief Classe para servidor SOAP
 */

if (!class_exists('SoapServer')) require_once dirname( __FILE__) . DIRECTORY_SEPARATOR . 'NuSOAP' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'nusoap.php';

class SOAP_Server extends Kernel {
	/// Classe utilizada internamente
	private $classUsed = NULL;
	/// Objeto SOAP server interno
	private $server = NULL;

	/**
	 *	\brief Construtor da classe
	 *
	 *	@param[in] (string) $endpoint - endereço URI do web service
	 *	@param[in] (array) $options - 
	 */
	public function __construct($endpoint='', $options=array()) {
		if (class_exists('SoapServer')) {
			$this->classUsed = 'SoapServer';

			$this->server = new SoapServer($endpoint, $options);
		} else {
			return false;
		}
	}

	/**
	 *	\brief Adiciona uma ou mais funções
	 */
	public function addFunction($functions) {
		if ($this->classUsed == 'SoapServer') {
			$this->server->addFunction($functions);
		} else {
			return false;
		}
	}

	/**
	 *	\brief 
	 */
	public function addSoapHeader($object) {
		if ($this->classUsed == 'SoapServer') {
			$this->server->addSoapHeader($object);
		} else {
			return false;
		}
	}

	/**
	 *	\brief Retorna a lista das funções definidas
	 */
	public function getFunctions() {
		if ($this->classUsed == 'SoapServer') {
			return $this->server->getFunctions();
		} else {
			return false;
		}
	}

	/**
	 *	\brief Lida com uma solicitação SOAP
	 */
	public function handle($soap_request=NULL) {
		if ($this->classUsed == 'SoapServer') {
			$this->server->handle($soap_request);
		} else {
			return false;
		}
	}

	/**
	 *	\brief Envia uma resposta para o cliente da requisição corrente, indicando um erro
	 */
	public function fault($code, $string, $actor=NULL, $details=NULL, $name=NULL) {
		if ($this->classUsed == 'SoapServer') {
			$this->server->fault($code, $string, $actor, $details, $name);
		} else {
			return false;
		}
	}

	/**
	 *	\brief Define a classe que lida com a solicitação SOAP
	 */
	public function setClass($class_name) {
		if ($this->classUsed == 'SoapServer') {
			$this->server->setClass($class_name);
		} else {
			return false;
		}
	}

	/**
	 *	\brief Define o objeto que será usado para lidar com a solicitação SOAP
	 */
	public function setObject($object) {
		if ($this->classUsed == 'SoapServer') {
			$this->server->setObject($object);
		} else {
			return false;
		}
	}

	/**
	 *	\brief Define o modo de persistência do servidor SOAP
	 */
	public function setPersistence($mode) {
		if ($this->classUsed == 'SoapServer') {
			$this->server->setPersistence($mode);
		} else {
			return false;
		}
	}
}
