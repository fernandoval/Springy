<?php
class JSON {
	private $dados = array();
	private $headerStatus = 200;
	
	public function __construct() {
		Kernel::set_conf('system', 'ajax', true);
		header('Content-type: application/json; charset=' . $GLOBALS['SYSTEM']['CHARSET'], true, $this->headerStatus);
	}
	
	public function add($dados) {
		$this->dados = array_merge($this->dados, $dados);
	}
	
	public function getDados() {
		return $this->dados;
	}
	
	public function setHeaderStatus($status) {
		$this->headerStatus = $status;
		header('Content-type: application/json; charset=' . $GLOBALS['SYSTEM']['CHARSET'], true, $this->headerStatus);
	}
	
	public function fetch() {
		return json_encode($this->dados);
	}
	
	public function printJ($andDie=true) {
		if (Kernel::get_conf('system', 'debug')) {
			$this->dados['debug'] = Kernel::get_debug();
		}
		
		echo $this->fetch();
		
		if ($andDie) {
			die;
		}
	}
	
	public function __toString() {
		$this->printJ();
	}
}
?>