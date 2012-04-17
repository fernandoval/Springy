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
		Framework error class
	------------------------------------------------------------------------------------ --- -- - */

class Errors extends Kernel {
	public static function showAjaxError($errorType, $msg='') {
		header('Content-type: text/html; charset=utf-8', true, $errorType);
		header('Status: '.$errorType);

		if (is_array($msg)) {
			echo json_encose($msg);
		} else if ($msg != '') {
			echo $msg;
		}

		die;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Encerra o processamento e dá saída na página de erro HTML
	    -------------------------------------------------------------------------------- --- -- - */
	public static function display_error($errorType, $msg='') {
		ob_clean();
		header('Content-type: text/html; charset=UTF-8', true, $errorType);

		Template::start();
		//Template::smartySetCommon();
		Template::Assign('errorDebug', (parent::get_conf('system', 'development') ? $msg : ''));
		Template::set_template('_error'.$errorType);
		Template::display();

		ob_end_flush();

		die;
	}

	public static function showFormError($local, $erros) {
		Template::smartyConfigLoad($local.'.conf');

		$return = array();

		for ($i=0; $i<count($erros); $i++) {
			$return[] = utf8_encode( Template::smartyGetConfigVars($erros[$i]) );
		}

		Template::smartyClearConfig();
		return $return;
	}

	public static function error_handler($errno, $errstr, $errfile, $errline) {
		switch ($errno){
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

		$d_bt = debug_backtrace();

		if (!parent::get_conf('system', 'ajax')) {
			Errors::display_error(500, '
				<div style="font-family:Arial, Helvetica, sans-serif; font-size:12px">
					<div style="background-color:#6666CC; color:#FFFFFF; font-weight:bold; padding-left:10px">Description error</div>
					<div>
						<span style="color:#FF0000">'.$printError.'</span>: <em>'.$errstr.'</em> in <strong>'.$errfile.'</strong> on line <strong>'.$errline.'</strong><br />
					</div>
					<br />
					<div style="background-color:#6666CC; color:#FFFFFF; font-weight:bold; padding-left:10px">Debug</div>
					<label style="width:140px; font-weight:bold; float:left">Protocolo:</label>'.$_SERVER['SERVER_PROTOCOL'].'<br />
					<label style="width:140px; font-weight:bold; float:left">URL:</label>'.Uri::getCurrentUrl().'<br />
					<label style="width:140px; font-weight:bold; float:left">Info:</label><pre>'.print_r($d_bt, true).'</pre>
					<br />
					<div style="background-color:#6666CC; color:#FFFFFF; font-weight:bold; padding-left:10px">IP</div>
					<label style="width:140px; font-weight:bold; float:left">IP:</label>'.$_SERVER['REMOTE_ADDR'].'<br />
					<label style="width:140px; font-weight:bold; float:left">Browser:</label>'.(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '').'<br />
					<br />
					<div style="background-color:#6666CC; color:#FFFFFF; font-weight:bold; padding-left:10px">VARS</div>
					<label style="width:140px; font-weight:bold; float:left">_POST</label><div style="width:80%"><pre>'.print_r($_POST, true).'</pre></div>
				</div>
			');
		} else {
			$this->showAjaxError(500, $printError.': '.$errstr."\nFile: $errfile\nLine: $errline");
		}
	}
}
?>