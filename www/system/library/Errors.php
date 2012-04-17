<?php
/**
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2011 FVAL Consultoria e Informática Ltda.\n
 *	Copyright (c) 2007-2011 Fernando Val\n
 *	Copyright (c) 2009-2011 Lucas Cardozo
 *
 *	\warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\version 1.0.3
 *
 *	\brief Classe para tratamento de erros
 */

class Errors extends Kernel {
	public static function ajax($errorType, $msg='') {
		self::error_handler(E_USER_ERROR, $msg, '', '', '', $errorType);
	}

	/**
	 *	\brief Encerra o processamento e dá saída na página de erro HTML
	 */
	public static function display_error($errorType, $msg='') {
		self::error_handler(E_USER_ERROR, $msg, '', '', '', $errorType);
	}

	/**
	 *	\brief Trata um erro ocorrido no sistema e encerra seu funcionamento
	 */
	public static function error_handler($errno, $errstr, $errfile, $errline, $localErro, $errorType=500) {
		DB::transactionAllRollBack();

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
			case E_RECOVERABLE_ERROR:
			default:
				return false;
			break;
		}

		self::send_report('<span style="color:#FF0000">'.$printError.'</span>'.($errstr ? ': <em>'.$errstr.'</em>':'').($errfile ?' in <strong>'.$errfile.'</strong> on line <strong>'.$errline.'</strong>' : ''), $errorType);

		die;
	}

	/**
	 *	\brief Monta a mensagem de erro com dados de backtrace, se aplicável
	 */
	public static function send_report($msg, $errorType, $adictionalInfo='') {
		$getParams = array();
		foreach (URI::get_params() as $var => $value) {
			$getParams[] = $var.'='.$value;
		}
		$msg = '
			<table width="100%" border="0" cellspacing="0" cellpadding="0" style="font-family:Arial, Helvetica, sans-serif; font-size:12px">
			  <tr>
				<td colspan="2" style="background-color:#66C; color:#FFF; font-weight:bold; padding-left:10px; padding:3px 2px">Description error</td>
			  </tr>
			  <tr>
				<td colspan="2" style="padding:3px 2px">'.$msg.'</td>
			  </tr>
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
				<td style="padding:3px 2px">'.URI::get_uri_string().'?'.implode('&', $getParams).'</td>
			  </tr>
			  <tr>
				<td valign="top" style="padding:3px 2px"><label style="font-weight:bold">Debug:</label></td>
				<td style="padding:3px 2px"><table width="100%"><tr><td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; padding:3px 2px">'.parent::get_debug().'</td></tr></table></td>
			  </tr>
			  <tr style="background:#efefef">
				<td valign="top" style="padding:3px 2px"><label style="font-weight:bold">Info:</label></td>
				<td style="padding:3px 2px"><table width="100%"><tr><td style="padding:3px 2px">'.parent::make_debug_backtrace().'</td></tr></table></td>
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
				<td style="padding:3px 2px">'.$_SERVER['REMOTE_ADDR'].'</td>
			  </tr>
			  <tr style="background:#efefef">
				<td style="padding:3px 2px"><label style="font-weight:bold">Reverso:</label></td>
				<td style="padding:3px 2px">'.gethostbyaddr($_SERVER['REMOTE_ADDR']).'</td>
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
				<td style="padding:3px 2px">'.parent::print_rc($_POST, true).'</td>
			  </tr>
			  <tr>
				<td valign="top" style="padding:3px 2px"><label style="font-weight:bold">_GET</label></td>
				<td style="padding:3px 2px">'.parent::print_rc($_GET, true).'</td>
			  </tr>
			  <tr>
				<td valign="top" style="padding:3px 2px"><label style="font-weight:bold">_COOKIE</label></td>
				<td style="padding:3px 2px">'.parent::print_rc($_COOKIE, true).'</td>
			  </tr>
			  <tr>
				<td valign="top" style="padding:3px 2px"><label style="font-weight:bold">_SESSION</label></td>
				<td style="padding:3px 2px">'.parent::print_rc(Session::get_all(), true).'</td>
			  </tr>
			</table>
		';

		// Envia a mensagem de erro para o webmaster
		if (!in_array($errorType, array(404, 503)) && parent::get_conf('mail', 'errors_go_to') && !parent::get_conf('system','debug')) {
			$msg = preg_replace('/\<a href="javascript\:\;" onclick="var obj=\$\((.*?)\)\.toggle\(\)" style="color:#06c; margin:3px 0"\>ver argumentos passados a função\<\/a\>/', '<span style="font-weight:bold; color:#06c; margin:3px 0">Argumentos da Função:</span>', $msg);
			$msg = preg_replace('/ style="display:none"/', '', $msg);

			$email = new Mail;
			$email->to(parent::get_conf('mail', 'errors_go_to'));
			$email->from(parent::get_conf('mail', 'errors_go_to'));
			$email->subject('Erro em - ' . parent::get_conf('uri', 'system_root'));
			$email->body($msg);
			$email->send();
			unset($email);
		}

		self::print_html($errorType, $msg);
	}

	/**
	 *	\brief Imprime a mensagem de erro
	 */
	public static function print_html($errorType, $msg) {
		// Verifica se a saída do erro não é em ajax ou json
		if (!parent::get_conf('system', 'ajax') || !in_array('Content-type: application/json', headers_list())) {
			if (ob_get_contents()) {
				ob_clean();
			}

			Template::start('_error' . $errorType);

			header('Content-type: text/html; charset=UTF-8', true, $errorType);

			Template::assign('urlJS',  URI::build_url(array('scripts'), array(), isset($_SERVER['HTTPS']), 'static'));
			Template::assign('urlCSS', URI::build_url(array('css'), array(), isset($_SERVER['HTTPS']), 'static'));
			Template::assign('urlIMG', URI::build_url(array('images'), array(), isset($_SERVER['HTTPS']), 'static'));
			Template::assign('urlSWF', URI::build_url(array('swf'), array(), isset($_SERVER['HTTPS']), 'static'));

			Template::assign('errorDebug', (parent::get_conf('system', 'debug') ? $msg : ''));
			Template::set_default_vars();

			Template::display();
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
