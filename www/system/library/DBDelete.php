<?php

namespace FW;

class DBDelete {
	private $tabela = '';
	private $conds = '';
	private $values = array();
	
	function __construct($tabela) {
		$this->tabela = $tabela;
	}
	
	public function addWhere(DBWhere $where) {
		if (!count($where->getValue())) {
			return;
		}
		
		$this->conds = $where;
		$this->addValues($where->getValue());
	}
	
	private function addValues($arrValues) {
		$this->values = $this->values + $arrValues;
	}
	
	public function getAllValues() {
		return $this->values;
	}
	
	public function __toString() {
		return 'DELETE FROM ' . $this->tabela . "\n"
			 . ($this->conds ? 'WHERE ' . $this->conds : '');
	}
}
?>