<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright	Copyright (c) 2007-2015 Fernando Val\n
 *
 *	\brief		Classe para servidor SOAP
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	0.4.4
 *  \author		Fernando Val  - fernando.val@gmail.com
 *	\ingroup	framework
 */

namespace FW;

/**
 *  \brief Classe para servidor SOAP
 *  
 *  \warning Esta classe ainda está em estágio experimental.
 */
class SOAP_Server extends Kernel {
	/// Objeto SOAP server interno
	private $server = NULL;

	/**
	 *	\brief Construtor da classe
	 *
	 *	@param[in] (string) $endpoint - endereço URI do web service
	 *	@param[in] (array) $options - 
	 */
	public function __construct($endpoint='', $options=array()) {
		$this->server = new \SoapServer($endpoint, $options);
	}

	/**
	 *	\brief Adiciona uma ou mais funções
	 */
	public function addFunction($functions) {
		$this->server->addFunction($functions);
	}

	/**
	 *	\brief 
	 */
	public function addSoapHeader($object) {
		$this->server->addSoapHeader($object);
	}

	/**
	 *	\brief Retorna a lista das funções definidas
	 */
	public function getFunctions() {
		return $this->server->getFunctions();
	}

	/**
	 *	\brief Lida com uma solicitação SOAP
	 */
	public function handle($soap_request=NULL) {
		$this->server->handle($soap_request);
	}

	/**
	 *	\brief Envia uma resposta para o cliente da requisição corrente, indicando um erro
	 */
	public function fault($code, $string, $actor=NULL, $details=NULL, $name=NULL) {
		$this->server->fault($code, $string, $actor, $details, $name);
	}

	/**
	 *	\brief Define a classe que lida com a solicitação SOAP
	 */
	public function setClass($class_name) {
		$this->server->setClass($class_name);
	}

	/**
	 *	\brief Define o objeto que será usado para lidar com a solicitação SOAP
	 */
	public function setObject($object) {
		$this->server->setObject($object);
	}

	/**
	 *	\brief Define o modo de persistência do servidor SOAP
	 */
	public function setPersistence($mode) {
		$this->server->setPersistence($mode);
	}
}
