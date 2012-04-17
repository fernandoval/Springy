<?php
/**
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2010 FVAL Consultoria e Informática Ltda.
 *
 *	\warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\version 0.9.4
 *
 *	\brief Classe para acesso a banco de dados
 *
 *	\note Esta classe usa a classe de acesso a banco de dados adoDB
 */

define('DB_RESTYPE_ASSOC', 0);
define('DB_RESTYPE_ARRAY', 1);
define('DB_RESTYPE_ROWS', 2);

require_once 'adoDB' . DIRECTORY_SEPARATOR . 'adodb.inc.php';

class DB extends Kernel {
	private static $DB = Array();

	private static $SQLRes = Array();

	private static $LastQuery = Array();

	private $sqlNum = 0;

	private $data;
	private static $printSql = 1;

	/**
	 *	\brief Conecta ao banco de dados
	 *
	 *	@param $database chave de configuração do banco de dados.
	 *		Default = 'default'
	 */
	public static function connect($database='default') {
		// Verifica se a instância já está definida e conectada
		if (isset(self::$DB[$database]) && self::$DB[$database]->IsConnected()) {
			return true;
		}
	
		// Lê as configurações de acesso ao banco de dados
		$conf = parent::get_conf('db', $database);

		// Cria uma nova instância de acesso
		self::$DB[$database] = ADONewConnection($conf['database_type']);

		if ($conf['persistent']) {
			$connected = self::$DB[$database]->PConnect($conf['host_name'], $conf['user_name'], $conf['password'], $conf['database']);
		} else {
			$connected = self::$DB[$database]->Connect($conf['host_name'], $conf['user_name'], $conf['password'], $conf['database']);
		}

		if (!$connected) {
			self::reportError('Can\'t connect to database server.', $database);
		}

		// Define o modo de recuperação de dados padrão
		self::$DB[$database]->SetFetchMode(ADODB_FETCH_ASSOC);

		self::execute('SET NAMES \''.$conf['charset'].'\'', false, false, $database);
	}

	/**
	 *	\brief Fecha a conexão com o banco de dados
	 *
	 *	@param $database chave de configuração do banco de dados.
	 *		Default = 'default'
	 */
	public function disconnect($database='default') {
		if (isset(self::$DB[$database]) && self::$DB[$database]->IsConnected()) {
			self::$DB[$database]->Disconnect();
		}
		unset(self::$DB[$database]);
	}

	/*
		[pt-BR] Método de retorno de erros. Também envia e-mails com informações sobre o erro e grava-o em um arquivo de Log.
	*/
	private static function reportError($msg, $database='default') {
		// [pt-br] Lê as configurações de acesso ao banco de dados
		$conf = parent::get_conf('db', $database);

		if (isset(self::$LastQuery[$database])) {
			$sqlError = str_replace('       ', "\n\t", self::$LastQuery[$database]);
		} else {
			$sqlError = 'Still this connection was not executed some instruction SQL using.';
		}
	
		$htmlError = '
			<div style="font-family:Arial, Helvetica, sans-serif; font-size:12px">
				<div style="background-color:6666CC; color:#FFFFFF; font-weight:bold; padding-left:10px">Description error</div>
				<label style="width:140px; font-weight:bold; float:left">Erro:</label><div style="width:80%">'.$msg . '<br />(' . self::$DB[$database]->ErrorNo() . ') '. self::$DB[$database]->ErrorMsg() . '</div>
				<label style="width:140px; font-weight:bold; float:left">SQL:</label><div style="width:80%">'.htmlentities($sqlError).'</div>
				<br />
				<div style="background-color:6666CC; color:#FFFFFF; font-weight:bold; padding-left:10px">Debug</div>
				<label style="width:140px; font-weight:bold; float:left">Debug:</label><pre>' . print_r(debug_backtrace(), true) . '</pre><br />
				<label style="width:140px; font-weight:bold; float:left">Protocolo:</label>'.$_SERVER['SERVER_PROTOCOL'].'<br />
				<label style="width:140px; font-weight:bold; float:left">URL:</label><br />
				<br />
				<div style="background-color:6666CC; color:#FFFFFF; font-weight:bold; padding-left:10px">IP</div>
				<label style="width:140px; font-weight:bold; float:left">IP:</label>'.$_SERVER['REMOTE_ADDR'].'<br />
				<label style="width:140px; font-weight:bold; float:left">Browser:</label>'.(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '').'<br />
				<br />
				<div style="background-color:6666CC; color:#FFFFFF; font-weight:bold; padding-left:10px">Dados do baco</div>
				<label style="width:140px; font-weight:bold; float:left">Host:</label>'.$conf['host_name'].'<br />
				<label style="width:140px; font-weight:bold; float:left">User:</label>'.$conf['user_name'].'<br />
				<label style="width:140px; font-weight:bold; float:left">Pass:</label>'.$conf['password'].'<br />
				<label style="width:140px; font-weight:bold; float:left">D.B.:</label>'.$conf['database'].'<br />
				<br />
				<div style="background-color:6666CC; color:#FFFFFF; font-weight:bold; padding-left:10px">VARS</div>
				<label style="width:140px; font-weight:bold; float:left">_GET</label><div style="width:80%"><pre>' . print_r($_GET, true) . '</pre></div>
				<label style="width:140px; font-weight:bold; float:left">_POST</label><div style="width:80%"><pre>' . print_r($_POST, true) . '</pre></div>
			</div>
		';
		unset($sqlError);

		// [pt-BR] Caso o sistema esteja em produção ($printSql == 1), mas não há um desenvolvedor vendo a página, manda e-mail.
		if (!parent::get_conf('system', 'development') && parent::get_conf('system', 'mail_error') && parent::get_conf('system', 'from_site') && parent::get_conf('system', 'log_mysql_file') && file_exists(parent::get_conf('system', 'log_mysql_file'))) {
			if (strpos(file_get_contents(parent::get_conf('system', 'log_mysql_file')), mysql_error() )) {

				$email_message = new EmailMessage;

				$email_message->SetBulkMail(1);
				$email_message->SetEncodedEmailHeader('From', parent::get_conf('system', 'mail_error'), parent::get_conf('sytem', 'site_name'));
				$email_message->SetEncodedHeader('Subject', 'Query error: '.parent::get_conf('sytem', 'site_name'));
				$email_message->cache_body = 0;
				$email_message->CreateQuotedPrintableHTMLPart($htmlError , '', $html_part);
				$email_message->SetEncodedEmailHeader('To', parent::get_conf('system', 'mail_error'), parent::get_conf('sytem', 'site_name'));
				$email_message->SetEncodedEmailHeader('Reply-To', parent::get_conf('system', 'mail_error'), parent::get_conf('sytem', 'site_name'));
				$email_message->SetEncodedEmailHeader('Errors-To', parent::get_conf('system', 'mail_error'), parent::get_conf('sytem', 'site_name'));

				$alternative_parts = array($html_part);
				$email_message->AddAlternativeMultipart($alternative_parts);

				if ($email_message->Send()) {
					$email_message->SetBulkMail(0);
				}
				unset($email_message);

				//mail(parent::get_conf('system', 'mail_error'), 'Query error: '.parent::get_conf('system', 'from_site'), $htmlError, 'From:"'.parent::get_conf('system', 'mail_error').'" <'.parent::get_conf('system', 'from_site').">\n"."Content-type:text/html\n");

				$fileErro = fopen(parent::get_conf('system', 'log_mysql_file'));
				fwrite($fileErro, "\n" );
				fwrite($fileErro, "\n=== BEGIN of ".date('d/m/Y H:i:s')." ===\n" );
				fwrite($fileErro, $erro );
				fwrite($fileErro, "\n=== FIM of ".date('d/m/Y H:i:s')." ===" );
				fwrite($fileErro, "\n==========================================" );
				fwrite($fileErro, "\n" );
				fclose($fileErro);
				unset($fileErro);
			}
		}

		if (self::$printSql == 2 || parent::get_conf('system', 'development')) {
			/*
				[pt-BR] Caso o sistema esteja em desenvolvimento OU algum desenvolvedor esteja vendo o sistema, imprime o erro no browser.
			*/
			if (parent::get_conf('system', 'ajax')) {
				Errors::ajax(500, array('Erro' => mysql_error(), 'No' => mysql_errno(), 'SQL' => addslashes( $this->DB->lastQuery )));
			} else {
				echo $htmlError;
			}
			die;
		}
	}

	/**
	 *	\brief Executa uma consulta no banco de dados
	 *
	 *	@param[in] $sql Comando SQL a ser executado
	 */
	public static function execute($sql, $params=false, $returnRows=false, $database='default') {
		if (URI::_GET('debugSQL') && parent::get_conf('system', 'development')) {
			Kernel::debug('SQL number:'.(++$this->sqlNum));
		}

		self::$LastQuery[$database] = $sql;
		if (($res = self::$DB[$database]->execute($sql, $params)) === false) {
			self::reportError('Can\'t execute query.', $database);
		}
		
		self::$SQLRes[$database] = $res;
		
		if ($returnRows) {
			return self::get_all($database);
		}
		return true;
	}
	/**
	 *	\brief Apelido para execute
	 *
	 *	\see execute
	 */
	public static function query($sql, $params=false, $returnRows=false, $database='default') {
		return self::execute($sql, $params, $returnRows, $database);
	}

	/**
	 *	\brief Escapa uma string para uso em comando SQL
	 */
	public static function escape_str($str, $database='default') {
		return self::$DB[$database]->Quote($str);
	}

	/**
	 *	\brief Retorna o último comando executado
	 */
	public static function last_query($database='default') {
		return self::$LastQuery[$database];
	}

	/**
	 *	\brief Retorna o valor do campo autoincremento do último INSERT
	 */
	public static function get_inserted_id($database='default') {
		$ret = self::$DB[$database]->Insert_ID();
		if (self::$DB[$database]->ErrorNo() > 0) {
			self::reportError('Can\'t get last inserted id.', $database);
		}
		return $ret;
	}

	/**
	 *	\brief Retorna o número de linhas afetadas no último comando
	 */
	public static function affected_rows($database='default') {
		$ret = self::$DB[$database]->Affected_Rows();
		if (self::$DB[$database]->ErrorNo() > 0) {
			self::reportError('Can\'t get number of affected rows.', $database);
		}
		return $ret;
	}

	/**
	 *	\brief Retorna o número de resultados de um SELECT
	 */
	public static function num_rows($database='default') {
		if (isset(self::$SQLRes[$database])) {
			return self::$SQLRes[$database]->RecordCount();
		}
		return false;
	}

	/**
	 *	\brief Retorna o resultado de um SELECT
	 */
	public static function get_all($database='default', $resultType=DB_RESTYPE_ROWS) {
		if (isset(self::$SQLRes[$database])) {
			switch ($resultType) {
				case DB_RESTYPE_ASSOC:
					$ret = self::$SQLRes[$database]->GetAssoc();
					break;
				case DB_RESTYPE_ARRAY:
					$ret = self::$SQLRes[$database]->GetArray();
					break;
				case DB_RESTYPE_ROWS:
					$ret = self::$SQLRes[$database]->GetRows();
					break;
				default:
					return false;
			}
			if (self::$DB[$database]->ErrorNo() > 0) {
				self::reportError('Can\'t get all results.', $database);
			}
			return $ret;
		}
		self::reportError('There is no resultset to get.', $database);
		return false;
	}

	/**
	 *	\brief Retorna o próximo resultado de um SELECT
	 */
	public static function fetch_next($database='default') {
		if (isset(self::$SQLRes[$database])) {
			return self::$SQLRes[$database]->FetchRow();
		}
		return false;
	}

	/**
	 *	\brief Retorna o valor de uma coluna do último registro pego por fetch_next
	 */
	public static function get_column($var, $database='default') {
		if (isset(self::$SQLRes[$database])) {
			return self::$SQLRes[$database]->Fields($var);
		}
		self::reportError($var.' is not defined in select (remember, it\'s a case sensitive) or $data is empty.', $database);
		return false;
	}

	/**
	 *	\brief Libera o resultset
	 */
	public static function free($database='default') {
		if (isset(self::$SQLRes[$database])) {
			self::$SQLRes[$database]->Close();
			unset(self::$SQLRes[$database]);
		}
		return false;
	}
}
?>