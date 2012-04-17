<?php
/**
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2009 FVAL Consultoria e Informática Ltda.
 *
 *	\warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\version 1.0.0
 *
 *	\brief Classe de pilha (FILO - First In Last Out)
 *
 *	Esta classe foi baseada no excelente trabalho de Pascal Rehfeldt.\n
 *	Conversão para PHP 5, melhorias, documentação e adaptação por Fernando Val.
 *
 *	\author (c) 2003 by Pascal Rehfeldt
 *	\author Pascal@Pascal-Rehfeldt.com
 *	\author Under license GNU General Public License (Version 2, June 1991)
 */

class FILO extends Kernel {
	private $elements = NULL;

	/**
	 *	\brief Construtor da classe
	 */
	public function __construct() {
		$this->zero();
	}

	/**
	 *	\brief Coloca um elemento na pilha
	 */
	public function push($elm) {
		array_push($this->elements, $elm);
	}

	/**
	 *	\brief Retira um elemento da pilha
	 */
	public function pop() {
		return array_pop($this->elements);
	}

	/**
	 *	\brief Limpa a pilha
	 */
	public function zero() {
		$this->elements = array();
	}
}
?>