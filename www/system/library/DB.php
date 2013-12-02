<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *	\copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *	\copyright Copyright (c) 2007-2013 Fernando Val\n
 *	\copyright Copyright (c) 2009-2013 Lucas Cardozo
 *
 *	\brief		Classe para acesso a banco de dados
 *	\note		Esta classe usa a PHP Data Object (PDO) para acesso a banco de dados
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	1.2.16
 *  \author		Fernando Val  - fernando.val@gmail.com
 *  \author		Lucas Cardozo - lucas.cardozo@gmail.com
 *	\ingroup	framework
 */

class DB {
	/// Guarda os IDs de conexão com os SGBDs
	private static $DB = array();
	/// SQL Resource
	private $SQLRes = NULL;
	/// Último comando executado
	private $LastQuery = '';
	/// Contador de comandos SQL executados
	private static $sqlNum = 0;
	/// Recurso de conexão atual
	private $dataConnect = false;
	/// Entrada de configuração de banco atual
	private $database = false;
	/// Flag de habilitação do relatório de erros
	private $report_error = false;
	/// Flag do modo debug
	private static $db_debug = false;
	/// Controle de falhas de conexão
	private static $conErrors = array();

	/**
	 *  \brief Método construtor da classe
	 *
	 *  Cria uma instância da classe a inicializa a conexão com o banco de dados
	 *
	 *  @param $database chave de configuração do banco de dados.
	 *	  Default = 'default'
	 */
	public function __construct($database='default') {
		$this->database = $database;
		$this->dataConnect = $this->connect($this->database);
	}

	/**
	 *  \brief Método destrutor da classe
	 *
	 *  Fecha todos os cursores, consultas em aberto com banco de dados e desinstancia a classe
	 */
	public function __destruct() {
		if (!is_null($this->SQLRes)) {
			$this->SQLRes->closeCursor();
			$this->SQLRes = NULL;
		}
	}

	/**
	 *  \brief Conecta ao banco de dados
	 *
	 *  @param $database chave de configuração do banco de dados.
	 *    Default = 'default'
	 *
	 *  @return Retorna o conector do banco de dados
	 */
	public function connect($database) {
		if (isset(self::$conErrors[$database])) {
			return false;
		}

		// Verifica se a instância já está definida e conectada
		if (isset(self::$DB[$database])) {
			return self::$DB[$database]['con'];
		}

		// Lê as configurações de acesso ao banco de dados
		$conf = Kernel::get_conf('db', $database);

		// Verifica se o servidor é um pool (round robin)
		if ($conf['database_type'] == 'pool' && is_array($conf['host_name'])) {
			return $this->_round_robin($database, $conf);
		}

		$pdoConf = array();
		if ($conf['database_type'] == 'mysql') {
			$pdoConf[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES \'UTF8\'';
		}

		if ($conf['persistent']) {
			$pdoConf[ PDO::ATTR_PERSISTENT ] = true;
		}

		/*
		 *  A variável abaixo é setada pois caso a conexão com o banco falhe, o callback de erro será chamado e a variável já estará setada.
		 *  Caso a conexão seja feita com sucesso, a variavel é removida.
		 */
		self::$conErrors[$database] = true;

		if (!$conf['host_name'] || !$conf['database']) {
			$this->report_error('HostName / DataBase not defined.');
		}

		//	a instância de conexão é estática, para nao criar uma nova a cada nova instãncia da classe
		self::$DB[$database] = array(
			'con' => new PDO(
				$conf['database_type'] . ':host=' . $conf['host_name'] . ';dbname=' . $conf['database'],
				$conf['user_name'],
				$conf['password'],
				$pdoConf
			),
			'dbName' => $database
		);
		unset($pdoConf, self::$conErrors[$database]);

		return self::$DB[$database]['con'];
	}

	/**
	 *	\brief Método privado de controle de round robin de conexão
	 *
	 *	Define o próximo servidor do pool de SGBDs.
	 *
	 *	@param $database chave de configuração do banco de dados.
	 *	@param $dbconf entradas de configuração do banco de dados.
	 *
	 *	@return Retorna o conector do banco de dados.
	 */
	private function _round_robin($database, $dbconf) {
		// Lê as configurações de controle de round robin
		$rr = Kernel::get_conf('db', 'round_robin');

		// Efetua controle de round robin por Memcached
		if ($rr['type'] == 'memcached') {
			$mc = new Memcached();
			$mc->addServer($rr['server_addr'], $rr['server_port']);

			// Define o próximo servidor do pool
			if (!($actual = (int)$mc->get('dbrr_' . $database))) {
				$actual = 0;
			}
			if (++$actual >= count($dbconf['host_name'])) {
				$actual = 0;
			}

			$mc->set('dbrr_'.$database, $actual, 0);
		}
		// Efetua controle de round robin em arquivo
		elseif ($rr['type'] == 'file') {
			// Define o próximo servidor do pool
			if ((!file_exists($rr['server_addr'] . DIRECTORY_SEPARATOR . 'dbrr_' . $database)) || !($actual = (int)file_get_contents($rr['server_addr'] . DIRECTORY_SEPARATOR . 'dbrr_' . $database))) {
				$actual = 0;
			}
			if (++$actual >= count($dbconf['host_name'])) {
				$actual = 0;
			}

			file_put_contents($rr['server_addr'] . DIRECTORY_SEPARATOR . 'dbrr_' . $database, $actual);
		}
		else {
			return false;
		}

		// Tenta conectar ao banco e retorna o resultado
		self::$DB[$database] = array(
			'con' => $this->connect($dbconf['host_name'][$actual]),
			'dbName' => $dbconf['host_name'][$actual]
		);
		return self::$DB[$database]['con'];
	}

	/**
	 *	\brief Método para verificar se a conexão com o banco foi feita
	 *
	 *	@param $database chave de configuração do banco de dados.
	 *		Default = 'default'
	 */
	public static function connected($database='default') {
		return !isset(self::$conErrors[$database]) && isset(self::$DB[$database]) && self::$DB[$database]['con'];
	}
	/**
	 *  \brief DEPRECATED - Use connected
	 *  \deprecated
	 *  \see connected
	 */
	public static function hasConnection($database='default') {
		self::connected($database);
	}

	/**
	 *	\brief Fecha a conexão com o banco de dados
	 */
	public function disconnect() {
		if ($this->dataConnect && $this->dataConnect->IsConnected()) {
			///	a instância de conexão é estática, para nao criar uma nova a cada nova instãncia da classe
			self::$DB[$this->database]['con']->Disconnect();
		}
		unset(self::$DB[$this->database]);
	}

	/**
	 *  \brief Desabilita o report de erros
	 *
	 *  \return DB
	 */
	public function disable_report_error() {
		$this->report_error = false;
		return $this;
	}
	/**
	 *  \brief DEPRECATED - Use disable_report_error
	 *  \deprecated
	 *  \see disable_report_error
	 */
	public function disableReportError() {
		return $this->disable_report_error();
	}

	/**
	 *  \brief Habilita o report de erros
	 *
	 *  \return DB
	 */
	public function enable_report_error() {
		$this->report_error = true;
		return $this;
	}
	/**
	 *  \brief DEPRECATED - Use enable_report_error
	 *  \deprecated
	 *  \see enable_report_error
	 */
	public function enableReportError() {
		return $this->enable_report_error();
	}

	/**
	 *	\brief Reporta a ocorrência de erro para o Webmaster
	 *
	 *	Método para alerta de erros. Também envia e-mails com informações sobre o erro e grava-o em um arquivo de Log.
	 */
	private function report_error($msg, PDOException $exception=NULL) {
		if(!$this->report_error) {
			return;
		}
		// [pt-br] Lê as configurações de acesso ao banco de dados
		$conf = Kernel::get_conf('db', self::$DB[$this->database]['dbName']);

		if (isset($this->LastQuery)) {
			$sqlError = '<pre>' . htmlentities((is_object($this->LastQuery) ? $this->LastQuery->__toString() : $this->LastQuery)) . '</pre><br /> Parametros:<br />' . Kernel::print_rc($this->LastValues, true);
		} else {
			$sqlError = 'Still this connection was not executed some instruction SQL using.';
		}

		$errorInfo = ($this->SQLRes ? $this->SQLRes->errorInfo() : $this->dataConnect->errorInfo());

		$htmlError = '
			<tr>
				<td style="background-color:#66C; color:#FFF; font-weight:bold; padding-left:10px; padding:3px 2px" colspan="2">Dados do banco</td>
			</tr>
			<tr>
				<td valign="top"><label style="font-weight:bold">Host:</label></td>
				<td>'.$conf['host_name'].'</td>
			</tr>
			<tr style="background:#efefef">
				<td valign="top"><label style="font-weight:bold">User:</label></td>
				<td><span style="background:#efefef">' . (isset($conf['user_name']) ? $conf['user_name'] : 'não informado') . '</span></td>
			</tr>
			<tr>
				<td valign="top"><label style="font-weight:bold">Pass:</label></td>
				<td>' . (isset($conf['password']) ? $conf['password'] : 'não informado') . '</td>
			</tr>
			<tr>
				<td valign="top"><label style="font-weight:bold">DB:</label></td>
				<td><span style="background:#efefef">' . (isset($conf['database']) ? $conf['database'] : 'não informado') . '</span></td>
			</tr>
		';
		unset($sqlError);

		Errors::send_report(
			'<span style="color:#FF0000">' . $msg . '</span> - ' . '(' . $errorInfo[1] . ') ' . $errorInfo[2] . ($exception ? '<br />' . $exception->getMessage() : '') . '<br /><pre>' . $this->LastQuery . '</pre><br />Valores: ' . Kernel::print_rc($this->LastValues, true),
			500,
			hash('crc32', $msg . $errorInfo[1] . $this->LastQuery), // error id
			$htmlError
		);

		die;
	}

	/**
	 *	\brief Alterna o estado de debug de banco de dados
	 */
	public static function debug($debug) {
		self::$db_debug = $debug;
		Kernel::debug('DEBUG DE BANCO DE DADOS: ' . ($debug ? 'LIGADO' : 'DESLIGADO'));
	}

	/**
	 *	\brief Inicializa uma transação
	 *
	 *	Como as transações podem utilzar varias classes e métodos, a transação será "estatica"
	 */
	public static function begin_transaction($database='default') {
		self::connect($database)->beginTransaction();
	}
	/**
	 *  \brief DEPRECATED - Use begin_transaction
	 *  \deprecated
	 *  \see begin_transaction
	 */
	public static function beginTransaction($database='default') {
		self::begin_transaction($database);
	}

	/**
	 *	\brief Cancela as alterações e termina a transação
	 *
	 *	Como as transações podem utilzar varias classes e métodos, a transação será "estatica"
	 */
	public static function rollback($database='default') {
		self::connect($database)->rollBack();
	}

	/**
	 *	\brief Confirma as alterações e termina a transação
	 *
	 *	Como as transações podem utilzar varias classes e métodos, a transação será "estatica"
	 */
	public static function commit($database='default') {
		self::connect($database)->commit();
	}

	/**
	 *	\brief Cancela todas as transações ativas
	 *
	 *	Em caso de erro no php, executa um all roll back.
	 *
	 *	Como as transações podem utilzar varias classes e métodos, a transação será "estatica"
	 */
	public static function rollback_all() {
		foreach (self::$DB as $db => $v) {
			if ($v['con']->inTransaction()) {
				Kernel::debug('DB ' . $db . ' rollback start');
				$v['con']->rollBack();
				Kernel::debug('DB ' . $db . ' rollback done!');
			}
		}
	}
	/**
	 *  \brief DEPRECATED - Use rollback_all
	 *  \deprecated
	 *  \see rollback_all
	 */
	public static function transactionAllRollBack() {
		self::rollback_all();
	}

	/**
	 *	Executa uma consulta no banco de dados
	 *
	 *	@param[in] $sql Comando SQL a ser executado
	 */
	public function execute($sql, array $where_v=array()) {
		self::$sqlNum++;

		$this->LastQuery = $sql;

		if (($sql instanceof DBSelect) || ($sql instanceof DBInsert) || ($sql instanceof DBUpdate) || ($sql instanceof DBDelete)) {
			$this->LastValues = $sql->getAllValues();
		} else {
			$this->LastValues = $where_v;
			$where_v = array();
		}

		$sql = NULL;

		if (($this->SQLRes = $this->dataConnect->prepare($this->LastQuery)) === false) {
			$this->report_error('Can\'t prepare query.');
		}

		if (count($this->LastValues)) {
			$numeric = 0;

			foreach($this->LastValues as $key => $where) {
				switch(gettype($where)) {
					case 'boolean' :
						$param = PDO::PARAM_BOOL;
					break;
					case 'integer' :
						$param = PDO::PARAM_INT;
					break;
					case 'NULL' :
						$param = PDO::PARAM_NULL;
					break;
					default :
						$param = PDO::PARAM_STR;
					break;
				}

				if (is_numeric($key)) {
					$this->SQLRes->bindValue(++$numeric, $where, $param);
				} else {
					$this->SQLRes->bindValue(':' . $key, $where, $param);
				}
			}
			unset($key, $where, $param, $numeric);
		}

		if ($this->SQLRes->execute() === false) {
			$this->report_error('Can\'t execute query.');
		}

		if (self::$db_debug || Kernel::get_conf('system', 'sql_debug')) {
			$conf = Kernel::get_conf('db', self::$DB[$this->database]['dbName']);

			Kernel::debug(
				'<pre>' .
					$this->LastQuery .
				'</pre><br />Valores: ' . Kernel::print_rc($this->LastValues, true) . '<br />' .
				'Affected Rows: ' . $this->affected_rows() . '<br />' .
				'DB: ' . (isset($conf['database']) ? $conf['database'] : 'não informado')
			, 'SQL #'  . self::$sqlNum, false);
		}

		return true;
	}

	/**
	 *	\brief Retorna o último comando executado
	 */
	public function last_query() {
		return $this->LastQuery;
	}

	/**
	 *	\brief Pega o nome do driver do banco
	 *
	 *	\return Retorna uma string contendo o nome do driver do banco de dados atual
	 */
	public function driver_name() {
		return $this->dataConnect->getAttribute(PDO::ATTR_DRIVER_NAME);
	}

	/**
	 *  \brief Pega algumas informações a respeito da versão do servidor
	 *  
	 *  \return Retorna um valor inteiro com a versão do servidor
	 */
	public function server_version() {
		return $this->dataConnect->getAttribute(PDO::PDO::ATTR_SERVER_VERSION);
	}
	
	/**
	 *	\brief Retorna o valor do campo autoincremento do último INSERT
	 */
	public function get_inserted_id($indice='') {
		return $this->dataConnect->lastInsertId( ((!$indice && $this->LastQuery instanceof DBInsert) ? $this->LastQuery->getTable() . '_id_seq' : $indice) );
	}

	/**
	 *	\brief Retorna o número de linhas afetadas no último comando
	 */
	public function affected_rows() {
		return $this->SQLRes->rowCount();
	}
	/**
	 *  \brief DEPRECATED - Use affected_rows
	 *  \deprecated
	 *  \see affected_rows
	 */
	public function num_rows() {
		return $this->affected_rows();
	}

	/**
	 *	\brief Retorna todas as linhas do resultado de uma consulta
	 */
	public function fetch_all($resultType=PDO::FETCH_ASSOC) {
		if ($this->SQLRes) {
			return $this->SQLRes->fetchAll($resultType);
		}

		return false;
	}
	/**
	 *  \brief DEPRECATED - Use fetch_all
	 *  \deprecated
	 *  \see fetch_all
	 */
	public function get_all($resultType=PDO::FETCH_ASSOC) {
		return $this->fetch_all($resultType);
	}

	/**
	 *	\brief Retorna o primeiro resultado do cursor de uma consulta
	 */
	public function fetch_first($resultType=PDO::FETCH_ASSOC) {
		if ($this->SQLRes) {
			return $this->SQLRes->fetch($resultType, PDO::FETCH_ORI_FIRST);
		}

		return false;
	}

	/**
	 *	\brief Retorna o resultado anterior do cursor de uma consulta
	 */
	public function fetch_prev($resultType=PDO::FETCH_ASSOC) {
		if ($this->SQLRes) {
			return $this->SQLRes->fetch($resultType, PDO::FETCH_ORI_PRIOR);
		}

		return false;
	}

	/**
	 *	\brief Retorna o próximo resultado de uma consulta
	 */
	public function fetch_next($resultType=PDO::FETCH_ASSOC) {
		if ($this->SQLRes) {
			return $this->SQLRes->fetch($resultType);
		}

		return false;
	}

	/**
	 *	\brief Retorna o último resultado do cursor de uma consulta
	 */
	public function fetch_last($resultType=PDO::FETCH_ASSOC) {
		if ($this->SQLRes) {
			return $this->SQLRes->fetch($resultType, PDO::FETCH_ORI_LAST);
		}

		return false;
	}

	/**
	 *	\brief Retorna o valor de uma coluna do último registro pego por fetch_next
	 */
	public function get_column($var=0) {
		if ($this->SQLRes && is_numeric($var)) {
			return $this->SQLRes->fetchColumn($var);
		}

		$this->report_error($var . ' is not defined in select (remember, it\'s a case sensitive) or $data is empty.');

		return false;
	}

	/**
	 *	\brief Converte uma data do formato brasileiro para o formato universal
	 *
	 *	@param $datetime String contendo a data e hora no formato brasileiro (d/m/Y H:n:s)
	 *	@param $flgtime Interruptor de concatenação da hora após a data
	 *
	 *	@return Retorna a data no formato universal (Y-m-d) concatenada da hora no formato universal (H:n:s), se o $flgtime for TRUE.
	 */
	public static function cast_date_br_to_db($datetime, $flgtime=false) {
		if (preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})/', $datetime, $res)) {
			$date = array($res[1], $res[2], $res[3]);
		} else {
			return '';
		}

		if ($flgtime) {
			if (preg_match('/([0-9]{2})\:([0-9]{2})\:([0-9]{2})$/', $datetime, $res)) {
				$time = array($res[1], $res[2], $res[3]);
			} else {
				return '';
			}
		}

		$date = $date[2] . '-' . $date[1] . '-' . $date[0];
		unset($res);

		if (!$flgtime) {
			return $date;
		}

		return $date . ' ' . $time[0] . ':' . $time[1] . ':' . $time[2];
	}

	/**
	 *	\brief Converte uma data do formato universal para o formato brasileiro 24h
	 *
	 *	@param $datetime String contendo a data e hora no formato universal (Y-m-d H:n:s)
	 *	@param $flgtime Interruptor de concatenação da hora após a data
	 *	@param $seconds Interruptor de concatenação dos segundos após a data
	 *
	 *	@return Retorna a data no formato brasileiro (d/m/Y) concatenada da hora no formato brasileiro 24h (H:n:s), se o $flgtime for TRUE.
	 */
	public static function cast_date_db_to_br($datetime, $flgtime=false, $sec=false) {
		if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})/', $datetime, $res)) {
			$date = array($res[1], $res[2], $res[3]);
		} else {
			return '';
		}

		if ($flgtime) {
			if (preg_match('/([0-9]{2})\:([0-9]{2})\:([0-9]{2})/', $datetime, $res)) {
				$time = array($res[1], $res[2], $res[3]);
			} else {
				return '';
			}
		}

		$date = $date[2] . '/' . $date[1] . '/' . $date[0];
		unset($res);

		if (!$flgtime) {
			return $date;
		}

		return $date . ' ' . $time[0] . ':' . $time[1] . ($sec ? ':' . $time[2] : '');
    }

	/**
	 *  \brief Converte em UNIX timestamp o valor data + hora no formato universal
	 *  
	 *  \param (string)$dateTime - data hora no format Y-m-d H:i:s
	 *  
	 *  \return Retorna o valor UNIX timestamp
	 */
	public static function mk_db_datetime($dateTime) {
		if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})/', $dateTime, $res)) {
			$data = array(
				$res[1],
				$res[2],
				$res[3],
			);
		} else if (preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})/', $dateTime, $res)) {
			$data = array(
				$res[3],
				$res[2],
				$res[1],
			);
		}
		unset($res);

		preg_match('/([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $dateTime, $res);
		if (isset($res[1])) {
			$hora = array(
				$res[1],
				$res[2],
				$res[3],
			);
		} else {
			$hora = array(
				0,
				0,
				0,
			);
		}
		unset($res);
		return mktime($hora[0], $hora[1], $hora[2], $data[1], $data[2], $data[0]);
    }
	/**
	 *  \brief DEPRECATED - Use mk_db_datetime
	 *  \deprecated
	 *  \see mk_db_datetime
	 */
	public static function dateToTime($dateTime) {
		return self::mk_db_datetime($dateTime);
	}

	/**
	 *  \brief Converte um valor datetime do banco em string de data brasileira
	 *  
	 *  \note Verificar real necessidade de manutenção desse método
	 *  \param (string)$dataTimeStamp - data hora no format Y-m-d H:i:s
	 *  \return Retorna uma string no formato '<dia> de <nome_do_mes>'.
	 */
	public static function lond_date_brazilian($dataTimeStamp) {
		$dateTime = DB::mk_db_datetime($dataTimeStamp);
		$mes = array('Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');
		return date('d', $dateTime) . ' de ' . $mes[date('m', $dateTime)];
    }
	/**
	 *  \brief DEPRECATED - Use lond_date_brazilian
	 *  \deprecated
	 *  \see lond_date_brazilian
	 */
	public static function dateToStr($dataTimeStamp) {
		return self::lond_date_brazilian($dataTimeStamp);
	}
}