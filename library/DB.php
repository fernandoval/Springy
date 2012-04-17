<?php
/*  ------------------------------------------------------------------------------------ --- -- -
	FVAL PHP Framework for Web Sites

	Copyright (C) 2009 FVAL - Consultoria e Informática Ltda.
	Copyright (C) 2009 Fernando Val
	Copyright (C) 2009 Lucas Cardozo

	http://www.fval.com.br

	Developer team:
		Fernando Val  - fernando.val@gmail.com
		Lucas Cardozo - lucas.cardozo@gmail.com

	Framework version:
		1.0.0

	Script version:
		1.0.0

	This script:
		Framework database class
	------------------------------------------------------------------------------------ --- -- - */

require_once dirname( __FILE__) . DIRECTORY_SEPARATOR . 'anyDB' . DIRECTORY_SEPARATOR . 'anyDB.php';

class DB extends Kernel {
	private static $DB = NULL;

	private static $sqlNum = 0;

	private $db_host, $db_user, $db_pass, $db_db, $db_persistent, $db_port;
	private $data;
	private $printSql = 1;

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Construtor da classe : Carrega os parâmetros de conexão da configuração
	    -------------------------------------------------------------------------------- --- -- - */
	function __construct($dbtype='', $uh='', $user='', $pass='', $db='') {
		// Cria uma layer
		$dbtype = ($dbtype ? $dbtype : parent::get_conf('db', 'database_type'));
		self::$DB = anyDB::getLayer($dbtype, '', $dbtype);
		self::$DB->$prefResType = ANYDB_RES_ASSOC;
		//self::$DB = anyDB::getLayer('MYSQL', '', 'mysql');
		//self::$DB = anyDB::getLayer('POSTGRESQL', '', 'pgsql');
		//self::$DB = anyDB::getLayer('SQLITE', '', 'sqlite');
		//self::$DB = anyDB::getLayer('DBX', '../../../inc/dbx/', 'dbx');

		//self::$DB = anyDB::getLayer('PEAR', 'c:/php4/pear/', $dbType);
		//self::$DB = anyDB::getLayer('PHPLIB', '../../../inc/phplib-7.2d/', $dbType);
		//self::$DB = anyDB::getLayer('METABASE', '../../../inc/metabase/', $dbType);
		//self::$DB = anyDB::getLayer('ADODB', '../../../inc/adodb/', $dbType);

		self::$db_host       = ($uh ? $uh : parent::get_conf('db', 'host_name'));
		self::$db_user       = ($user ? $user : parent::get_conf('db', 'user_name'));
		self::$db_pass       = ($pass ? $pass : parent::get_conf('db', 'password'));
		self::$db_db         = ($db ? $db : parent::get_conf('db', 'database'));
		self::$db_persistent = ($db ? $db : parent::get_conf('db', 'persistent'));
		self::connect();
		//self::query(debug_backtrace(), 'SET CHARACTER SET utf8');
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Conecta ao banco de dados
	    -------------------------------------------------------------------------------- --- -- - */
	public static function connect() {
		self::$DB->connect(self::$db_host, self::$db_db, self::$db_user, self::$db_pass, self::$db_persistent);
		if (self::$DB->error) {
			self::reportError('Can\'t connect to database server.');
		}

		if (self::$db_db) {
			self::setDB(self::$db_db);
		}

		//self::query(debug_backtrace(), 'SET CHARACTER SET utf8');
		//self::query('SET NAMES \'utf8\'');
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Fecha a conexão com o banco de dados
	    -------------------------------------------------------------------------------- --- -- - */
	public static function disconnect() {
		self::$DB->disconnect();
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Seleciona o banco de dados/esquema
	    -------------------------------------------------------------------------------- --- -- - */
	public static function setDB($db) {
		self::$db_db = $db;
		self::$DB->setDB(self::$db_db);
		if (self::$DB->error) {
			self::reportError('Can\'t select database "'.self::$db_db.'".');
		}
	}

	/*
		[pt-BR] Método de retorno de erros. Também envia e-mails com informações sobre o erro e grava-o em um arquivo de Log.
	*/
	private static function reportError($msg) {
		if (self::$DB->lastQuery) {
			$sqlError = str_replace('       ', "\n\t", self::$DB->lastQuery);
		} else {
			$sqlError = 'Still this connection was not executed some instruction SQL using.';
		}

		$htmlError = '
			<div style="font-family:Arial, Helvetica, sans-serif; font-size:12px">
				<div style="background-color:6666CC; color:#FFFFFF; font-weight:bold; padding-left:10px">Description error</div>
				<label style="width:140px; font-weight:bold; float:left">Erro:</label><div style="width:80%">'.$msg . '<br />' . self::$DB->error . '</div>
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
				<label style="width:140px; font-weight:bold; float:left">Host:</label>'.self::$db_host.'<br />
				<label style="width:140px; font-weight:bold; float:left">User:</label>'.self::$db_user.'<br />
				<label style="width:140px; font-weight:bold; float:left">Pass:</label>'.self::$db_pass.'<br />
				<label style="width:140px; font-weight:bold; float:left">D.B.:</label>'.self::$db_db.'<br />
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
				Errors::ajax(500, array('Erro' => mysql_error(), 'No' => mysql_errno(), 'SQL' => addslashes( self::$DB->lastQuery )));
			} else {
				echo $htmlError;
			}
			die;
		}
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Executa uma consulta no banco de dados
	    -------------------------------------------------------------------------------- --- -- - */
	public static function query($sql, $returnRows=false, $resultType = ANYDB_PREDEFINED_VALUE) {
		if (Uri::getQueryString('debugSQL') && parent::get_conf('system', 'development')) {
			echo '<div style="text-align:left; font-family:Arial; font-size:10px"><pre>SQL number:' . (++self::$sqlNum). " \n".
			$sql.'</pre></div><br />'."\n";
		}

		if (!self::$DB->query($sql)) {
			self::reportError('Can\'t execute query.');
		}

		if ($returnRows) {
			return self::$DB->getAll($resultType);
		}
		return true;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Executa uma consulta no banco de dados
	    -------------------------------------------------------------------------------- --- -- - */
	public static function execute($sql, $resultType = ANYDB_PREDEFINED_VALUE) {
		return self::query($sql, true, $resultType);
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Escapa uma string para uso em comando SQL
	    -------------------------------------------------------------------------------- --- -- - */
	public static function escape_str($str) {
		return self::$DB->escapeStr($str);
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Retorna a última query executada
	    -------------------------------------------------------------------------------- --- -- - */
	public static function last_query() {
		return self::$DB->lastQuery;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Retorna a última id inserido
	    -------------------------------------------------------------------------------- --- -- - */
	public static function get_inserted_id() {
		$ret = self::$DB->getInsertId();
		if (self::$DB->error) {
			self::reportError('Can\'t get last inserted id.');
		}
		return $ret;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Retorna o número de linhas afetadas
	    -------------------------------------------------------------------------------- --- -- - */
	public static function affected_rows() {
		$ret = self::$DB->affectedRows();
		if (self::$DB->error) {
			self::reportError('Can\'t get number of affected rows.');
		}
		return $ret;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Retorna o número de resultados de um SELECT
	    -------------------------------------------------------------------------------- --- -- - */
	public static function num_rows() {
		$ret = self::$DB->numRows();
		if (self::$DB->error) {
			self::reportError('Can\'t get number of result rows.');
		}
		return $ret;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Retorna o resultado de um SELECT
	    -------------------------------------------------------------------------------- --- -- - */
	public static function get_all($resultType = ANYDB_PREDEFINED_VALUE) {
		$ret = self::$DB->getAll($resultType);
		if (self::$DB->error) {
			self::reportError('Can\'t get all results.');
		}
		self::$data = array();
		return $ret;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Retorna o próximo resultado de um SELECT
	    -------------------------------------------------------------------------------- --- -- - */
	public static function get_next($resultType = ANYDB_PREDEFINED_VALUE) {
		$ret = self::$DB->getNext($resultType);
		if (self::$DB->error) {
			self::reportError('Can\'t get next result.');
		}
		self::$data = $ret;
		return $ret;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Retorna o valor de uma coluna do último registro pego por get_next
	    -------------------------------------------------------------------------------- --- -- - */
	public static function get_column($var) {
		if (!@array_key_exists($var, self::$data)) {
			self::reportError($var.' is not defined in select (remember, it\'s a case sensitive) or $data is empty.');
		}

		return self::$data[$var];
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Retorna o último registro pego por get_next
	    -------------------------------------------------------------------------------- --- -- - */
	public static function get_data() {
		return self::$data;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Libera o resultset
	    -------------------------------------------------------------------------------- --- -- - */
	public static function free() {
		self::$data = NULL;
		self::$DB->free();
	}
}
?>