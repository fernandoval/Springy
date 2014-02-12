<?php

namespace FW;

class DBUpdate {
	private $tabela = NULL;
	private $columns = array();
	private $conds = NULL;
	private $from = NULL;
	private $values = array();
	
	function __construct($tabela) {
		$this->tabela = $tabela;
	}
	
	public function add($valores) {
		foreach($valores as $key => $value) {
			$this->columns[] = $key;
			$this->values[] = $value;
		}
	}
	
	public function addWhere(DBWhere $where) {
		$this->conds = $where;
		
		if (!count($where->getValue())) {
			return;
		}
		
		$this->addValues($where->getValue());
	}
	
	public function setFrom(DBSelect $from, $apelido='x') {
		$this->from = '(' . $from . ') ' . $apelido;
		$this->addValues($from->getAllValues());
	}
	
	private function addValues($arrValues) {
		foreach ($arrValues as $value) {
			$this->values[] = $value;
		}
	}
	
	public function getAllValues() {
		return $this->values;
	}
	
	public function __toString() {
		return 'UPDATE ' . $this->tabela . ' SET' . "\n"
			 . '    ' . implode(' = ?, ' . "\n    ", $this->columns) . ' = ?' . "\n"
			 . ($this->from  ? 'FROM ' . $this->from . "\n" : '')
			 . ($this->conds ? 'WHERE ' . $this->conds : '-- sem conds');
	}
}
?>