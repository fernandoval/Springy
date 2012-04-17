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
		Framework kernel class
	------------------------------------------------------------------------------------ --- -- - */

class Kernel {
	private static $confs = array();
	private static $debug = array();

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Põe uma informação na janela de debug
	    -------------------------------------------------------------------------------- --- -- - */
	public static function debug($txt) {
		if (self::get_conf('system', 'debug') == true) {
			if (is_array($txt) || is_object($txt)) {
				self::$debug[] = '<pre>' . print_r($txt, true) . '</pre>';
			} else {
				self::$debug[] = $txt;
			}
		}
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Imprime o resultado do debug
	    -------------------------------------------------------------------------------- --- -- - */
	public static function debug_print() {
		if (self::get_conf('system', 'debug') == true && !empty(self::$debug)) {
			echo '<style>
			.debug_box { font-size:11px; font-family:Verdana; color:#000000; background: #FFF; margin:0; width:99%; display:block; position:absolute; top:0; left:0; text-decoration:none; text-align:center; border: 2px solid #F93; border-top:none }
			.debug_box_top_asd .debug_box_2 { display:none }
			.debug_box_top_asd_hover .debug_box_2{ border-top:2px solid #F93; display:block; height:300px; color:#000000; overflow:auto; text-align:left; padding:0 5px }
			.debug_box_3 { background-image:url(data:image/gif;base64,R0lGODlhBQAbAMQAAP+mIf/aov/CZv/rzP+xO//PiP/15v/ku/+7Vf/89//Jd//TkP+qKv/hs//x3f+3TP/Mf//dqv/Fbv/u1f+0Q//47v/nxP++Xf/////Wmf+tMgAAAAAAAAAAAAAAAAAAACH5BAAHAP8ALAAAAAAFABsAAAVFICaKSVlWKGqsq+O6UxwPNG3d96HrTd9HQGBgOMwYjYtkssBkQp5PhVQqqVYFWOxlu0V4vY9wmEImE85njVrNaLcBcHgIADs=) }
			</style>
			<div class="debug_box">
			<div class="debug_box_top_asd" onmouseover="this.className=\'debug_box_top_asd_hover\'" onmouseout="this.className=\'debug_box_top_asd\'"><div class="debug_box_2">';

			for ($i=0; $i<count(self::$debug); $i++) {
				echo ($i>0 ? "<br />\n<hr /><br />\n" : '') . self::$debug[$i];
			}

			echo '</div><div class="debug_box_3">DEBUG</div></div></div>';
		}
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Pega o conteúdo de um registro de configuração
		$local = nome do arquivo
		$var   = registro desejado
	    -------------------------------------------------------------------------------- --- -- - */
	public static function get_conf($local, $var) {
		if (!isset(self::$confs[$local][$var])) {
			self::load_conf$local);
		}
		return (isset(self::$confs[$local][$var]) ? self::$confs[$local][$var] : NULL);
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Carrega um arquivo de configuração
		$local = nome do arquivo
	    -------------------------------------------------------------------------------- --- -- - */
	public static function load_conf$local) {
		require $GLOBALS['SYSTEM']['CONFIG_PATH'] . DIRECTORY_SEPARATOR . $local . '.conf.php';
		self::$confs[ $local ] = $conf[ $GLOBALS['SYSTEM']['ACTIVE_ENVIRONMENT'] ];
	}
}
?>