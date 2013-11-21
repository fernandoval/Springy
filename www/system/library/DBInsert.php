<?php
class DBInsert {
	private $tabela = '';
	private $columns = array();
	private $values = array();

	function __construct($tabela) {
		$this->tabela = $tabela;
	}

	public function add($valores) {
		foreach($valores as $key => $value) {
			$this->columns[] = $key;
			$this->values[] = $value;
		}
		return $this;
	}

	public function getAllValues() {
		return $this->values;
	}

	public function getTable() {
		return $this->tabela;
	}

	public function __toString() {
		return 'INSERT INTO ' . $this->tabela . '(' . "\n"
			 . '    ' . implode(', ' . "\n    ", $this->columns) . "\n"
			 . ') VALUES ('
			 . '    ' . implode(', ' . "\n    ", array_fill(0, count($this->columns), '?')) . "\n"
			 . ')';
	}
}
?>