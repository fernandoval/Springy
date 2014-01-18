<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *	\copyright Copyright (c) 2007-2014 FVAL Consultoria e Informática Ltda.
 *	\copyright Copyright (c) 2007-2014 Fernando Val
 *	\copyright Copyright (c) 2009-2013 Lucas Cardozo
 *
 *	\brief		Classe para tratamento de erros
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	1.5.12
 *  \author		Fernando Val  - fernando.val@gmail.com
 *  \author		Lucas Cardozo - lucas.cardozo@gmail.com
 *	\ingroup	framework
 */

namespace FW;

/**
 *  \brief Classe para tratamento de erros
 *  
 *  Esta classe é estática e invocada automaticamente pelo framework.
 */
class Errors {
	public static function ajax($errorType, $msg='') {
		self::errorHandler(E_USER_ERROR, $msg, '', '', '', $errorType);
	}

	/**
	 *	\brief Encerra o processamento e dá saída na página de erro HTML
	 */
	public static function displayError($errorType, $msg='') {
		$debug = debug_backtrace();
		self::errorHandler(E_USER_ERROR, $msg, $debug[0]['file'], $debug[0]['line'], '', $errorType);
	}

	/**
	 *	\brief Trata um erro ocorrido no sistema e encerra seu funcionamento
	 */
	public static function errorHandler($errno, $errstr, $errfile, $errline, $localErro, $errorType=500) {
		if (
			strpos($errfile, 'template') !== false || strpos($errfile, 'Smarty') !== false
			|| strpos($errstr, 'filemtime') !== false
		   ) {
			return;
		}

		DB::rollBackAll();

		switch ($errno) {
			case E_ERROR:
				$printError = 'Error';
			break;
			case E_WARNING:
				$printError = 'Warning';
			break;
			case E_PARSE:
				$printError = 'Parse Error';
			break;
			case E_NOTICE:
				$printError = 'Notice';
			break;
			case E_CORE_ERROR:
				$printError = 'Core Error';
			break;
			case E_CORE_WARNING:
				$printError = 'Core Warning';
			break;
			case E_COMPILE_ERROR:
				$printError = 'Compile Error';
			break;
			case E_COMPILE_WARNING:
				$printError = 'Compile Warning';
			break;
			case E_USER_ERROR:
				$printError = 'User Error';
			break;
			case E_USER_WARNING:
				$printError = 'User Warning';
			break;
			case E_USER_NOTICE:
				$printError = 'User Notice';
			break;
			case E_STRICT:
				$printError = 'Fatal Error';
			break;
			case 1044:
				$printError = 'Access Denied to Database';
			break;
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				$printError = 'Deprecated Function';
			break;
			case E_RECOVERABLE_ERROR:
				$printError = 'Fatal Error';
			break;
			default:
				$printError = 'Unknown Error';
			break;
		}

		self::sendReport(
			'<span style="color:#FF0000">'.$printError.'</span>'.($errstr ? ': <em>'.$errstr.'</em>':'').($errfile ?' in <strong>'.$errfile.'</strong> on line <strong>'.$errline.'</strong>' : ''),
			$errorType,
			hash('crc32', $errno . $errfile . $errline) /// error id
		);

		die;
	}

	/**
	 *	\brief Monta a mensagem de erro com dados de backtrace, se aplicável
	 */
	public static function sendReport($msg, $errorType, $errorId, $adictionalInfo='') {
		$getParams = array();
		foreach (URI::getParams() as $var => $value) {
			$getParams[] = $var.'='.$value;
		}

		$msg = '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="font-family:Arial, Helvetica, sans-serif; font-size:12px">
			  <tr>
				<td style="background-color:#66C; color:#FFF; font-weight:bold; padding-left:10px; padding:3px 2px">Description error</td>
			  </tr>
			  <tr>
				<td style="padding:3px 2px">'.$msg.'</td>
			  </tr>
			  <tr>
				<td style="padding:3px 2px"><strong>Error ID:</strong> '.$errorId.' (<a href="'.URI::buildURL(array('_system_bug_solved_', $errorId)).'">marcar bug como resolvido</a>)</td>
			  </tr>
			  <tr style="color:#000; display:none" class="bugList">
				<td style="padding:3px 2px"><strong>Número de ocorrencias:</strong> [n_ocorrencias] | <strong>Última ocorrência:</strong> [ultima_ocorrencia] (<a href="javascript:;">mais informações</a>)</td>
			  </tr>
			  <tr class="hideMoreInfo">
			    <td colspan="2">
			      <table width="100%" border="0" cellspacing="0" cellpadding="0" style="font-family:Arial, Helvetica, sans-serif; font-size:12px">
					  <tr>
						<td colspan="2" style="background-color:#66C; color:#FFF; font-weight:bold; padding-left:10px; padding:3px 2px">Debug</td>
					  </tr>
					  <tr style="background:#efefef">
						<td style="padding:3px 2px"><label style="font-weight:bold">Tempo de execução da página até aqui:</label></td>
						<td style="padding:3px 2px">' . number_format(microtime(true) - $GLOBALS['FWGV_START_TIME'], 6) . ' segundos</td>
					  </tr>
					  <tr>
						<td style="padding:3px 2px"><label style="font-weight:bold">Sistema:</label></td>
						<td style="padding:3px 2px">'.php_uname('n').'</td>
					  </tr>
					  <tr style="background:#efefef">
						<td style="padding:3px 2px"><label style="font-weight:bold">Modo Seguro:</label></td>
						<td style="padding:3px 2px">'.(ini_get('safe_mode') ? 'Sim' : 'Não').'</td>
					  </tr>
					  <tr>
						<td style="padding:3px 2px"><label style="font-weight:bold">Data:</label></td>
						<td style="padding:3px 2px">'.date('Y-m-d').'</td>
					  </tr>
					  <tr style="background:#efefef">
						<td style="padding:3px 2px"><label style="font-weight:bold">Horario:</label></td>
						<td style="padding:3px 2px">'.date('G:i:s').'</td>
					  </tr>
					  <tr>
						<td style="padding:3px 2px"><label style="font-weight:bold">Request:</label></td>
						<td style="padding:3px 2px">'.$_SERVER['REQUEST_URI'].'</td>
					  </tr>
					  <tr style="background:#efefef">
						<td style="padding:3px 2px"><label style="font-weight:bold">Request method:</label></td>
						<td style="padding:3px 2px">'.$_SERVER['REQUEST_METHOD'].'</td>
					  </tr>
					  <tr>
						<td style="padding:3px 2px"><label style="font-weight:bold">Protocol:</label></td>
						<td style="padding:3px 2px">'.$_SERVER['SERVER_PROTOCOL'].'</td>
					  </tr>
					  <tr style="background:#efefef">
						<td style="padding:3px 2px"><label style="font-weight:bold">URL:</label></td>
						<td style="padding:3px 2px">'.URI::getURIString().'?'.implode('&', $getParams).'</td>
					  </tr>
					  <tr>
						<td valign="top" style="padding:3px 2px"><label style="font-weight:bold">Debug:</label></td>
						<td style="padding:3px 2px"><table width="100%"><tr><td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; padding:3px 2px">'.Kernel::getDebugContent().'</td></tr></table></td>
					  </tr>
					  <tr style="background:#efefef">
						<td valign="top" style="padding:3px 2px"><label style="font-weight:bold">Info:</label></td>
						<td style="padding:3px 2px"><table width="100%"><tr><td style="padding:3px 2px">'.Kernel::makeDebugBacktrace().'</td></tr></table></td>
					  </tr>
					  <tr>
						<td colspan="2" style="background-color:#66C; color:#FFF; font-weight:bold; padding-left:10px; padding:3px 2px">IP</td>
					  </tr>
					  <tr>
						<td style="padding:3px 2px"><label style="font-weight:bold">Referer:</label></td>
						<td style="padding:3px 2px">'.(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '').'</td>
					  </tr>
					  <tr style="background:#efefef">
						<td style="padding:3px 2px"><label style="font-weight:bold">IP:</label></td>
						<td style="padding:3px 2px">'.Strings::getRealRemoteAddr().'</td>
					  </tr>
					  <tr style="background:#efefef">
						<td style="padding:3px 2px"><label style="font-weight:bold">Reverso:</label></td>
						<td style="padding:3px 2px">'. (Strings::getRealRemoteAddr() ? gethostbyaddr(Strings::getRealRemoteAddr()) : 'sem ip') .'</td>
					  </tr>
					  <tr>
						<td style="padding:3px 2px"><label style="font-weight:bold">Browser:</label></td>
						<td style="padding:3px 2px">'.(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '').'</td>
					  </tr>
					  '.$adictionalInfo.'
					  <tr>
						<td colspan="2" style="background-color:#66C; color:#FFF; font-weight:bold; padding-left:10px; padding:3px 2px">VARS</td>
					  </tr>
					  <tr>
						<td valign="top" style="padding:3px 2px"><label style="font-weight:bold">_POST</label></td>
						<td style="padding:3px 2px">'.Kernel::print_rc($_POST, true).'</td>
					  </tr>
					  <tr>
						<td valign="top" style="padding:3px 2px"><label style="font-weight:bold">_GET</label></td>
						<td style="padding:3px 2px">'.Kernel::print_rc($_GET, true).'</td>
					  </tr>
					  <tr>
						<td valign="top" style="padding:3px 2px"><label style="font-weight:bold">_COOKIE</label></td>
						<td style="padding:3px 2px">'.Kernel::print_rc($_COOKIE, true).'</td>
					  </tr>
					  <tr>
						<td valign="top" style="padding:3px 2px"><label style="font-weight:bold">_SESSION</label></td>
						<td style="padding:3px 2px">'.Kernel::print_rc(Session::getAll(), true).'</td>
					  </tr>
					</table>
				</td>
			  </tr>
			</table>';

		// Envia a mensagem de erro para o webmaster
		if (!in_array($errorType, array(404, 503)) && Configuration::get('mail', 'errors_go_to') && !Configuration::get('system','debug')) {
			$db = new DB;
			if (DB::hasConnection()) {
				$db->execute('SELECT 1 FROM system_error WHERE error_code = ?', array($errorId));

				if ($db->affectedRows()) {
					$naoMandaEmail = true;
				}

				$db->execute('INSERT INTO system_error (error_code, detalhes) VALUES (?, ?) ON DUPLICATE KEY UPDATE qtd = qtd + 1', array($errorId, $msg));
			}
			unset($db);

			if (!isset($naoMandaEmail)) {
				$msg = preg_replace('/\<a href="javascript\:\;" onclick="var obj=\$\(\#(.*?)\)\.toggle\(\)" style="color:#06c; margin:3px 0"\>ver argumentos passados a função\<\/a\>/', '<span style="font-weight:bold; color:#06c; margin:3px 0">Argumentos da Função:</span>', $msg);
				$msg = preg_replace('/ style="display:none"/', '', $msg);

				$email = new Mail;
				$email->to(Configuration::get('mail', 'errors_go_to'));
				$email->from(Configuration::get('mail', 'errors_go_to'));
				$email->subject('Erro em ' . $GLOBALS['SYSTEM']['SYSTEM_NAME'] . ' (release: "' . $GLOBALS['SYSTEM']['SYSTEM_VERSION'] . '" | ambiente: "' . ($GLOBALS['SYSTEM']['ACTIVE_ENVIRONMENT'] ? $GLOBALS['SYSTEM']['ACTIVE_ENVIRONMENT'] : $_SERVER['HTTP_HOST']) . '")' . ' - ' . ((isset($_SERVER) && isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : ""));
				$email->body($msg);
				$email->send();
				unset($email);
			}
		}

		self::printHtml($errorType, $msg);
	}

	/**
	 *	\brief Marga um bug como resolvido
	 */
	public static function bugSolved($errorId) {
		$db = new DB;
		if (DB::hasConnection()) {
			$db->execute('DELETE FROM system_error WHERE error_code = ?', array($errorId));
		}
		unset($db);
		die('Bug <strong>' . URI::getSegment(1, false) . '</strong> marcado como resolvido.');
	}

	/**
	 *	\brief Lista os bugs registrados
	 */
	public static function bugList() {
		$tpl = new Template('_buglist');

		$nPorPagina = 5;

		$pag = (int)URI::_get('pag');
		if ($pag < 1) {
			$pag = 1;
		}
		$offset = ($pag - 1) * $nPorPagina;

		$db = new DB;
		$order_column = URI::getParam('orderBy')?:'ultima_ocorrencia';
		$order_type = URI::getParam('sort')?:'ASC';
		$db->execute(
			"SELECT SQL_CALC_FOUND_ROWS qtd, ultima_ocorrencia, detalhes\n" .
			"  FROM system_error\n" .
			" ORDER BY $order_column $order_type\n".
			" LIMIT ?, ?",
			array($offset, $nPorPagina)
		);

		$retorno = array();

		while ($res = $db->fetchNext()) {
			$res['detalhes'] = preg_replace('/<tr style="color:#000; display:none" class="bugList">/', '<tr class="bugList">', $res['detalhes']);
			$res['detalhes'] = preg_replace('/<tr class="hideMoreInfo">/', '<tr style="color:#000; display:none">', $res['detalhes']);
			$res['detalhes'] = str_replace('[ultima_ocorrencia]', DB::leData($res['ultima_ocorrencia'], true, true), $res['detalhes']);


			$retorno[] = str_replace('[n_ocorrencias]', $res['qtd'], $res['detalhes']);
		}

		$db->execute('SELECT FOUND_ROWS() AS total');
		$res = $db->fetchNext();

		$paginacao = new Pagination();
		$paginacao->setRowsPerPage($nPorPagina);
		$paginacao->setCurrentPage($pag);
		$paginacao->setNumRows($res['total']);
		$paginacao->setSiteLink(array('_system_bug_'));

		$tpl->assign('paginacao', $paginacao->parse());
		$tpl->assign('orders', array(
			'error_code' => URI::buildURL(array('_system_bug_')),
			'qtd' => URI::buildURL(array('_system_bug_')),
			'ultima_ocorrencia' => URI::buildURL(array('_system_bug_'))
		));
		unset($paginacao, $registros);

		$tpl->assign('errors', $retorno);
		$tpl->display();
		die;
	}

	/**
	 *	\brief Imprime a mensagem de erro
	 */
	public static function printHtml($errorType, $msg) {
		// Verifica se a saída do erro não é em ajax ou json
		//if (!Configuration::get('system', 'ajax') || !in_array('Content-type: application/json; charset=' . $GLOBALS['SYSTEM']['CHARSET'], headers_list())) {
		if (!URI::isAjaxRequest()) {
			if (ob_get_contents()) {
				ob_clean();
			}

			$tpl = new Template('_error' . $errorType);

			header('Content-type: text/html; charset=UTF-8', true, $errorType);

			$tpl->assign('urlJS',  URI::buildURL(array('scripts'), array(), isset($_SERVER['HTTPS']), 'static'));
			$tpl->assign('urlCSS', URI::buildURL(array('css'), array(), isset($_SERVER['HTTPS']), 'static'));
			$tpl->assign('urlIMG', URI::buildURL(array('images'), array(), isset($_SERVER['HTTPS']), 'static'));
			$tpl->assign('urlSWF', URI::buildURL(array('swf'), array(), isset($_SERVER['HTTPS']), 'static'));

			$tpl->assign('errorDebug', (Configuration::get('system', 'debug') ? $msg : ''));

			$tpl->display();
			unset($tpl);
		} else {
			header('Content-type: application/json; charset=utf-8', true, $errorType);
			if (is_array($msg)) {
				echo json_encode($msg);
			} else if ($msg != '') {
				echo $msg;
			}
		}
	}
}
