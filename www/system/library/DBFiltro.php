<?php

namespace FW;

class DBFiltro extends DBExpression {
	private $cond = '';
	private $valor = NULL;
	
	/**
	 *	@param Boolean $valorColuna (informa se a variavel $valor deve ser tratada como uma coluna do select, inner join, etc.).
	 */
	public function __construct($coluna, $operador, $valor=NULL, $valorColuna=false) {
		// valida
		switch ($operador) {
			case parent::IN :
			case parent::NOT_IN :
				if (!is_array($valor)) {
					throw new Exception('$valor precisa ser um array.');
				}
				
				$this->cond = $coluna . ' ' . $operador . '(' . implode(', ', array_fill(0, count($valor), '?')) . ')';
			break;
			case parent::ISNULL :
			case parent::ISNOTNULL :
				$this->cond = $coluna . ' ' . $operador;
			break;
			case parent::LIKE :
				$this->cond = sprintf(DBExpression::LOWER, $coluna) . ' ' . $operador . ' ' . sprintf(DBExpression::LOWER, '?');
			break;
			default :
				$this->cond = $coluna . ' ' . $operador . ' ' . (!$valorColuna ? '?' : $valor);
			break;
		}
		
		if (!$valorColuna) {
			$this->valor = $valor;
		}
	}
	
	public function getValue() {
		return $this->valor;
	}
	
	public function __toString() {
		return $this->cond;
	}
}
?>