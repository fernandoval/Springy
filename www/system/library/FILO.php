<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *	\copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.
 *	\copyright Copyright (c) 2007-2013 Fernando Val
 *	\copyright Copyright (c) 2003 by Pascal Rehfeldt
 *
 *	\brief		Classe de pilha (FILO - First In Last Out)
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	1.1.2
 *	\author		Pascal@Pascal-Rehfeldt.com
 *	\author		Under license GNU General Public License (Version 2, June 1991)
 *	\ingroup	framework
 *
 *	\note		Esta classe foi baseada no excelente trabalho de Pascal Rehfeldt.\n
 *	Conversão para PHP 5, melhorias, documentação e adaptação por Fernando Val.
 */

namespace FW;

/**
 *  \brief Classe de pilha (FILO - First In Last Out)
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
