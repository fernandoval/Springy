<?php
/**	\file
 *  FVAL PHP Framework for Web Applications
 *
 *  \copyright Copyright (c) 2007-2014 FVAL Consultoria e Informática Ltda.
 *  \copyright Copyright (c) 2007-2014 Fernando Val
 *  \copyright Copyright (c) 2009-2013 Lucas Cardozo
 *
 *  \brief		Script da classe cerne do framework
 *  \warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version	1.6.32
 *  \author		Fernando Val  - fernando.val@gmail.com
 *  \author		Lucas Cardozo - lucas.cardozo@gmail.com
 *  \ingroup	framework
 */

namespace FW;

/**
 *  \brief Classe cerne do framework
 *  
 *  Esta classe é estática e invocada automaticamente pelo framework.
 */
class Kernel {
	/// Versão do framework
	const VERSION = '2.1.2';
	/// Array interno com dados de configuração
	private static $confs = array();
	/// Array com informações de debug
	private static $debug = array();
	/// Determina se o usuário está usando dispositivo móvel
	private static $mobile = NULL;
	/// Determina o tipo de dispositivo móvel
	private static $mobile_device = NULL;
	/// Determina o root de controladoras
	private static $controller_root = array();
	/// Caminho do namespace do controller
	private static $controller_namespace = null;


	/**
	 *  \brief Pega ou seta o root de controladoras
	 *  
	 *  \param (array)$controller_root - ae definido, altera o root de controladoras
	 *  \return Retorna um array contendo o root de controladoras
	 */
	public static function controllerRoot($controller_root=null) {
		if (!is_null($controller_root)) {
			self::$controller_root = $controller_root;
		}
		return self::$controller_root;
	}

	/**
	 *	\brief Define o namespace da controller a ser carregada
	 *
	 *	\param string $controller
	 */
	public static function controllerNamespace($controller=null) {
		if(!is_null($controller) && file_exists($controller)) {
			$controller  = pathinfo($controller);
			$controller = str_replace($GLOBALS['SYSTEM']['CONTROLER_PATH'], '', $controller['dirname']);
			$controller = str_replace(DIRECTORY_SEPARATOR, '/', $controller);
			self::$controller_namespace = trim($controller, DIRECTORY_SEPARATOR);
		}
		return self::$controller_namespace;
	}

	/**
	 *	\brief Põe uma informação na janela de debug
	 */
	public static function debug($txt, $name='', $highlight=true, $revert=true) {
		$debug =  array(
			memory_get_usage(true),
			$name,
			$highlight,
			$txt,
			debug_backtrace()
		);
		if ($revert) {
			array_unshift(self::$debug, $debug);
		} else {
			self::$debug[] = $debug;
		}
	}

	/**
	 *	\brief Imprime o bloco de debug
	 *
	 *	\return void
	 */
	public static function debugPrint() {
		if (!defined('STDIN') && Configuration::get('system', 'debug') == true && !Configuration::get('system', 'sys_ajax')) {
			$size = memory_get_peak_usage(true);
			$unit = array('b', 'KB', 'MB', 'GB', 'TB', 'PB');
			$memoria = round($size / pow(1024, ($i = floor(log($size,1024)))), 2) . ' ' . $unit[$i];
			unset($unit, $size);

			self::debug('Tempo de execução de página: ' . number_format(microtime(true) - $GLOBALS['FWGV_START_TIME'], 6) . ' segundos' . "\n" . 'Pico de memória: ' . $memoria, '', true, false);
			unset($memoria);

			$conteudo = ob_get_contents();
			ob_clean();

			$htmlDebug ='<!-------------------- inicio do código de debug -------------------->' .
						'<style type="text/css">.debug_box {background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEgAACxIB0t1+/AAAABZ0RVh0Q3JlYXRpb24gVGltZQAwOC8wNC8xMLtLDxEAAAAcdEVYdFNvZnR3YXJlAEFkb2JlIEZpcmV3b3JrcyBDUzVxteM2AAAADUlEQVQImWP4////GQAJyAPKSOz6nwAAAABJRU5ErkJggg==); z-index: 99999; margin:0; width:70px; height:30px; display:block; position:fixed; bottom:0; left:0; text-decoration:none; border: 2px solid #06C}.debug_box * {color:#000; font-weight:normal; font-family:Verdana; font-size:11px; text-align:left; border:0; margin:0; padding:0}.debug_box_3 {cursor:pointer; font-weight: bold; color:#06C; text-align:center }.debug_box_3.close {line-height:30px}.debug_box_3.open {background:url(data:image/gif;base64,R0lGODlhBQAbAMQAAP+mIf/aov/CZv/rzP+xO//PiP/15v/ku/+7Vf/89//Jd//TkP+qKv/hs//x3f+3TP/Mf//dqv/Fbv/u1f+0Q//47v/nxP++Xf/////Wmf+tMgAAAAAAAAAAAAAAAAAAACH5BAAHAP8ALAAAAAAFABsAAAVFICaKSVlWKGqsq+O6UxwPNG3d96HrTd9HQGBgOMwYjYtkssBkQp5PhVQqqVYFWOxlu0V4vY9wmEImE85njVrNaLcBcHgIADs=); line-height:auto; height:auto}.debug_box_3.red {color:#f00}.debug_box_2 .tools {margin:3px}.debug_box_2 .tools a.close {padding:9px 2px 9px 11px; background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAK6wAACusBgosNWgAAABx0RVh0U29mdHdhcmUAQWRvYmUgRmlyZXdvcmtzIENTNXG14zYAAAAVdEVYdENyZWF0aW9uIFRpbWUAMi8xNy8wOCCcqlgAAABcSURBVBiVhY/REYAwCENTr/tYJ8oomYWJdKP61R7gneQPeITQJA0AN/51QdKsJGkea8XMPja+t0GSYWBmILnr7h087KHgWCmA61yOEcCcKcPhmSzfa5JOAE8RcbyUIkZhBhiUxQAAAABJRU5ErkJggg==) no-repeat left center; color:#f00}.debug_box_2 .tools a.clear {padding:9px 2px 9px 17px; background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAMCAYAAABr5z2BAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAANrwAADa8BQr/nKgAAABx0RVh0U29mdHdhcmUAQWRvYmUgRmlyZXdvcmtzIENTNXG14zYAAAHrSURBVCiRjZK/axNhGMe/7/tez7sY7HkphVLTH4tDTEBSSqHUxU1xcOofIE3i4B8hjg4Wx6QUXBzMppNjQmuh0BBiIQ2loAnhwPSC0eQS7t7e+7jYotZWv+MDnw/PL0ZE+J98zGTuMKIEhCin8vnGaZ39S1DJ5cYMxp4zIXKmbdPIdbki8hnn+3I0enSpoJbN3hRCvLsai8WnFhYiQtehwhDNUkkF/T5XRC+1i+D9TCarCfFiMpk0rbk5DgBepwNnb0+ZlkWGbaPXbPbOdVBfW7NJ11+PGcbK9NJSVI9GAQBOpUIDx2GTqRSFvh92Dw+HKgzv/iaoZbMPOGNvIrZ9Jb68LBjnkJ6H1vZ2yDWNTaXT3G00hkPX/cSI7ify+daZoJbLrWiMvY9MTBijbpcrIiZ0XSkpmTU7q67F46K9u+vRyUkRrvs4USwGAKCFAA2PjqCbJqbTaZixGAAgGAzwuVzm1+fnoY+P8+bW1pCUepIqFF79OrLmOw5a6+u4sbh4BgNA4HngjKlQyuBLtfqVSXkvublZ+3PZnBsGjJkZONUqjg8OEHgeAOC4XkcoJfvebn+Qvp+49RcYANjJzyX4nQ6+lUro7exA6Dpkvw9S6mmyUHiGS57l/BlXV3VpWQ+JyL+9sfH2IvA0PwDhFvArpErTbgAAAABJRU5ErkJggg==) no-repeat left center; color:#f00}.debug_box_2 .tools a:hover {text-decoration:underline}.debug_box_2 .debug_info_area {overflow:auto; height:97%}.debug_box_2 .debug_info { overflow: auto }.debug_box_2 .debug_info:hover {background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEgAACxIB0t1+/AAAABx0RVh0U29mdHdhcmUAQWRvYmUgRmlyZXdvcmtzIENTNXG14zYAAAAWdEVYdENyZWF0aW9uIFRpbWUAMDQvMjUvMTHJkGH2AAAADUlEQVQImWP4+/v2GQAJbAOgd8SdQAAAAABJRU5ErkJggg==)} .debug_box_2 .debug_box_ajax_result > span { font-weight: bold }.debug_box_2 .debug_box_ajax_result > p { padding-left:30px }.debug_box_2 table {margin:5px 0; width:100%}.debug_box_2 table th {font-weight: bold; color:#e8740d; font-size:14px; border:0} .debug_box_2 table td {padding:0 0 3px 3px; vertical-align:top}.debug_box_2 a {color:#7250a2}.debug_box_2 > hr, .debug_box_2 .debug_info_area > hr {margin: 15px 0; border:1px solid #d8dade; visibility:visible}.debug_box_2 .ErrorTitle{background-color:#66C; color:#FFF; font-weight:bold; padding-left:10px}.debug_box_2 .ErrorZebra{background:#efefef}.debug_box_2 .ErrorLabel{font-weight:bold}</style>' .
						//'<script type="text/javascript" src="/aff.js"></script>' .
						'<script type="text/javascript">'.
						'Debug={opened:false,pe:null,w:window,p:null,open:function(){$(\'#debug\').css({\'height\':\'auto\',\'width\':(parseInt($(window).width())-5)+\'px\'}).find(\'div\').first().show().css({\'height\':(parseInt($(window).height())-18)+\'px\'}).next(\'.debug_box_3\').hide();Debug.opened=true;try{try{Debug.p.cancel()}catch(e){}clearInterval(Debug.pe);Debug.pe=null}catch(e){}},close:function(){$(\'#debug\').css({\'width\':\'70px\',\'height\':\'\'}).find(\'div\').first().hide().next(\'.debug_box_3\').show().removeClass(\'red\').fadeTo(1,1);Debug.opened=false},clear:function(){$(\'#debug\').find(\'.debug_info_area\').first().html(\'\')},
						init:function(){var iniciaDoPrincipal=true;do{try{if(Debug.w.document.location==window.top.document.location){break}else{iniciaDoPrincipal=false;Debug.w=Debug.w.parent;$(\'#debug\').hide()}}catch(e){break}}while(true);if(!iniciaDoPrincipal){Debug.printAjaxResults(\'\',{},$(\'#debug\').find(\'.debug_info_area\').first().html())}$(\'#debug\').find(\'.debug_box_3\').first().bind(\'click\',Debug.open);$(\'#debug\').find(\'.close\').first().bind(\'click\',Debug.close);$(\'#debug\').find(\'.clear\').first().bind(\'click\',Debug.clear);if($(\'#debug div.debug_box_2 div.debug_info_area div.debug_info\').size()>1){Debug.startPulsate()}},pulsate:function(){$(\'#debug\').find(\'.debug_box_3\').first().addClass(\'red\');$(\'#debug\').find(\'.debug_box_3\').first().fadeOut(100,function(){$(\'#debug\').find(\'.debug_box_3\').first().fadeIn(100,function(){$(\'#debug\').find(\'.debug_box_3\').first().fadeOut(100,function(){$(\'#debug\').find(\'.debug_box_3\').first().fadeIn(100,function(){})})})})},startPulsate:function(){try{Debug.p.cancel()}catch(e){}Debug.p=1;Debug.pulsate();if(Debug.pe==null){Debug.pe=setInterval(Debug.pulsate,5000)}},printAjaxResults:function(titulo,infos,txt){var d=Debug.w.$(\'.debug_box\');if(d.size()){var now=new Date();var to=d[0].find(\'.debug_box_2\').first().find(\'.debug_info_area\').first();var title=\'Resultado do AJAX feito \';if(window.parent.document.location!==self.document.location){title+=\'no [i]?frame \'+window.name+\' \'}param=\'\';if(infos.param){infos.param=infos.param.split(\'&\');infos.param.each(function(p){param=(param?\', \':\'\')+\'[\'+p+\']\'})}var span=new Element(\'span\').insert(titulo);if(infos.url){span.insert(\'<br />\').insert(\'URL: \'+infos.url)}if(param){span.insert(\'<br />\').insert(\'Parametros:\'+param)}span.insert(\'<br />\').insert(\'Hora: \'+(now.getHours()<10?\'0\':\'\')+now.getHours()+\':\'+(now.getMinutes()<10?\'0\':\'\')+now.getMinutes()+\':\'+(now.getSeconds()<10?\'0\':\'\')+now.getSeconds()+\'\');var dbar=new Element(\'div\').addClass(\'debug_box_ajax_result\').insert(span);if(txt){dbar.insert(new Element(\'p\').insert(txt))}to.insert({top:new Element(\'hr\')}).insert({top:dbar});if(txt&&!Debug.w.Debug.opened){Debug.w.Debug.startPulsate()}}}};jQuery(Debug.init);DebugAjaxComplete=function(e, XMLHttpRequest, ajaxOptions, thrownError){var p={url:e.url,params:e.parameters};if(e.transport.status===\'500\'){Debug.printAjaxResults(\'Error 500\',p,e.transport.responseText)}else if(e.transport.responseText){if(e.transport.responseJSON){var json=e.transport.responseJSON}else if(e.transport.responseText.isJSON()){var json=e.transport.responseText.evalJSON()}else{Debug.printAjaxResults(\'Result AJAX\',p,e.transport.responseText);return}Debug.printAjaxResults(\'Result JSON\',p,(json.debug?json.debug:\'\'))}};$(window).bind(\'ajaxComplete\',DebugAjaxComplete);$(window).bind(\'ajaxSuccess\',DebugAjaxComplete);$(window).bind(\'ajaxError\',DebugAjaxComplete);'.
						'</script>' .
						'<div class="debug_box" id="debug">' .
							 '<div class="debug_box_2" style="display:none">' .
								'<div class="tools">' .
									'<a class="clear" href="javascript:;">Limpar</a>' .
									'<a class="close" href="javascript:;">Fechar</a>' .
								'</div>' .
								'<div class="debug_info_area">' .
								self::getDebugContent() .
								'</div>' .
							 '</div>' .
							 '<div class="debug_box_3 close">DEBUG</div>' .
						'</div>' .
						'<!-------------------- fim   do código de debug -------------------->';

			if (preg_match('/<body(.*?)>/', $conteudo)) {
				echo preg_replace('/<body(.*?)>/', '<body\\1>' . $htmlDebug, $conteudo);
			} else {
				echo preg_replace('/^(.*?)$/', '<script type="text/javascript" src="'.URI::buildURL(array('scripts'), array(), true, 'static').'/jquery.js"></script>' . $htmlDebug . '\\1', $conteudo);
			}
		}
	}

	/**
	 *	\brief Junta o conteúdo do array de debug numa string com separador visual
	 *
	 *	\return Retorna uma string contendo os dados capturados em debug
	 */
	public static function getDebugContent() {
		$return = array();
		foreach(self::$debug as $debug) {
			$id      = 'debug_' . str_replace('.', '', current(explode(' ', microtime())));

			$unit = array('b', 'KB', 'MB', 'GB', 'TB', 'PB');
			$memoria = round($debug[0] / pow(1024, ($i = floor(log($debug[0],1024)))), 2) . ' ' . $unit[$i];

			$return[] = '
			<div class="debug_info">
				<table width="100%" border="0" cellspacing="0" cellpadding="0" align="left">
				  <thead>
					<th colspan="2" align="left">' . ($debug[1] ? $debug[1] . ' - ' : '') . 'Memória Alocada até aqui: ' . $memoria . '</th>
				  </thead>
				  <tr>
					<td width="50%" valign="top"> ' . ($debug[2] ? self::print_rc($debug[3]) : $debug[3]) . '</td>
					<td width="50%" valign="top">
						<a href="javascript:;" onclick="var obj=$(\'#' . $id . '\').toggle()">Debug BackTrace</a>
						<div id="' . $id . '" style="display:none">' . self::makeDebugBacktrace($debug[4]) . '</div></td>
				  </tr>
				</table>
			</div>
			';
		}
		return implode('<hr />', $return);
	}

	/**
	 *	\brief Imprime os detalhes de uma variável em cores
	 *
	 *	\param[in] (variant) $par - variável
	 *	\param[in] (bool) $return - sem utilização
	 *	\return Retorna uma string HTML
	 */
	public static function print_rc($par, $return=false) {
		if (is_object($par)) {
			if (method_exists($par, '__toString')) {
				return str_replace('&lt;?php', '', str_replace('?&gt;', '', highlight_string('<?php ' . var_export($par->__toString(), true) . ' ?>', true ) )) .
				(($par instanceof DBSelect || $par instanceof DBInsert || $par instanceof DBUpdate || $par instanceof DBDelete) ? '<br />' . str_replace('&lt;?php', '', str_replace('?&gt;', '', highlight_string('<?php ' . var_export($par->getAllValues(), true) . ' ?>', true ) )) : '');
			} else {
				return '<pre>' . print_r($par, true) . '</pre>';
			}
		} else {
			return str_replace('&lt;?php', '', str_replace('?&gt;', '',
				highlight_string('<?php ' . print_r($par, true) . ' ?>', true )
			));
		}
	}

	/**
	 *	\brief Monta o texto do debug backtrace
	 *
	 *	\param[in] (array) $debug array com o backtrace gerado
	 */
	public static function makeDebugBacktrace($debug = null) {
		if(!is_array($debug)) {
			$debug = debug_backtrace();
		}
		array_shift($debug);

		$aDados = array();

		foreach($debug as $value) {
			if (empty($value['line']) || strpos($value['file'], 'Errors.php') > 0) {
				continue;
			}

			$linhas = explode('<br />', str_replace('<br /></span>', '</span><br />', highlight_string( file_get_contents($value['file']), true)));
			$aDados[] = array(
				'arquivo' => $value['file'],
				'linha' => $value['line'],
				'args' => isset($value['args']) ? $value['args'] : 'Sem argumentos passados',
				'conteudo_linha' => trim( preg_replace('/^(&nbsp;)+/', '', $linhas[ $value['line'] - 1 ] ))
			);
		}

		$tr = 0;
		$saida = '    <ul style="font-family:Arial, Helvetica, sans-serif; font-size:12px">';
		$i  = 0;
		$li = 0;

		foreach($aDados as $key => $backtrace) {
			if ($backtrace['linha'] > 0) {
				$backtrace['conteudo_linha'] = preg_replace('/^<\/span>/', '', trim($backtrace['conteudo_linha']));
				if (!preg_match('/<\/span>$/', $backtrace['conteudo_linha'])) {
					$backtrace['conteudo_linha'] .= '</span>';
				}

				$linha  = sprintf('[%05d]', $backtrace['linha']);
				$saida .= '      <li style="margin-bottom: 5px; '.($li +1 < count($aDados) ? 'border-bottom:1px dotted #000; padding-bottom:5px' : '').'">'
					   .  '        <span style="' . ($i == 1 ? ' color:#F00; ' : '') . '"><b>' . $linha . '</b>&nbsp;<b>' . $backtrace['arquivo'] . '</b></span><br />'
					   .  '        ' . $backtrace['conteudo_linha'];

				if (count($backtrace['args'])) {
					$id     = 'args_' . str_replace('.', '', current(explode(' ', microtime())));
					$saida .= '        <br />' . "\n"
						   .  '        <a href="javascript:;" onClick="var obj=$(\'#' . $id . '\').toggle()" style="color:#06c; margin:3px 0">ver argumentos passados a função</a>'
						   .  '        ' . (is_array($backtrace['args']) ? '<div id="'.$id.'" style="display:none">' . self::print_rc($backtrace['args'], true) . '</div>' : $backtrace['args']);
				}
				$saida .= '      </li>';
				$li++;
			}
			$tr++;
		}
		return $saida . '</ul>';
	}

	/**
	 *	\brief Converte um array multidimensional no objeto stdClass
	 *
	 *	\param[in] $array (mixed) array a ser convertido
	 *	\return Retorna um objeto stdClasse
	 */
	public static function arrayToObject($array) {
		if(!is_array($array)) {
			return $array;
		}

		$object = new stdClass();
		if (count($array) > 0) {
			foreach ($array as $name => $value) {
				$name = trim($name);
				if (!empty($name)) {
					$object->$name = self::arrayToObject($value);
				}
			}
			return $object;
		}

		return FALSE;
	}

	/**
	 *	\brief Converte um objeto num array multidimensional
     *
	 *	\param[in] $object (mixed) objeto a ser convertido
	 *	\return Retorna um array
     */
    public static function objectToArray($object) {
        if (is_object($object)) {
            $object = get_object_vars($object);
			if (count($object) > 0) {
				foreach ($object as $name => $value) {
					$name = trim($name);
					if (!empty($name)) {
						$object[$name] = self::objectToArray($value);
					}
				}
			}
        }

		return $object;
    }

	/**
	 *  \brief Verifica se o usuário está usando um browser de dispositivo móvel
	 *  
	 *  Esse método está depreciado e será removido na versão 2.1.
	 *  
	 *  Use o método FW\Browser\OS::isMobile
	 *  
	 *  Este método verifica se o navegador do usuário é de um dispositivo móvel.
	 *  A partir da versão 2.0 foi adiciona o conjunto de classes Browser, mais completa,
	 *  e que permite verificar mais dados do navegador e sistema operacional.
	 *  Veja as classes Browser\OS, Browser\Browser e Browser\Language.
	 *  
	 *  \deprecated
	 *  \see FW\Browser\OS::isMobile
	 */
	private static function mobileDeviceDetect() {
		// Define que não é um dispositivo móvel até que seja provado o contrário
		self::$mobile = false;
		// Define que não é um dispositivo móvel até que seja provado o contrário
		self::$mobile_device = NULL;

		// verifica se o USER AGENT existe (acesso via API não possui user agent)
		if(!isset($_SERVER['HTTP_USER_AGENT'])){
			return self::$mobile;
		}

		// Pega o valor do USER AGENT
		$user_agent = $_SERVER['HTTP_USER_AGENT'];

		switch (true) {
			// iPad
			case (preg_match('/ipad/i',$user_agent));
				//$mobile_browser = $ipad; // mobile browser is either true or false depending on the setting of ipad when calling the function
				self::$mobile = true;
				self::$mobile_device = 'Apple iPad';
				break;
			// iPhone ou iPod?
			case (preg_match('/ipod/i',$user_agent)||preg_match('/iphone/i',$user_agent));
				self::$mobile = true;
				self::$mobile_device = 'Apple';
				break;
			// Android?
			case (preg_match('/android/i',$user_agent));
				self::$mobile = true;
				if(preg_match('/mobile/i',$user_agent)){
					self::$mobile_device = 'Android Mobile';
				} else {
					self::$mobile_device = 'Android';
				}
				break;
			// Opera Mini?
			case (preg_match('/opera mini/i',$user_agent));
				self::$mobile = true;
				self::$mobile_device = 'Opera';
				break;
			// Blackberry?
			case (preg_match('/blackberry/i',$user_agent));
				self::$mobile = true;
				self::$mobile_device = 'Blackberry';
				break;
			// Palm?
			case (preg_match('/(pre\/|palm os|palm|hiptop|avantgo|plucker|xiino|blazer|elaine)/i',$user_agent));
				self::$mobile = true;
				self::$mobile_device = 'Palm';
				break;
			// Windows Mobile?
			case (preg_match('/(iris|3g_t|windows ce|opera mobi|windows ce; smartphone;|windows ce; iemobile)/i',$user_agent));
				self::$mobile = true;
				self::$mobile_device = 'Windows Smartphone';
				break;
			// Outros dispositivos móveis conhecidos?
			case (preg_match('/(mini 9.5|vx1000|lge |m800|e860|u940|ux840|compal|wireless| mobi|ahong|lg380|lgku|lgu900|lg210|lg47|lg920|lg840|lg370|sam-r|mg50|s55|g83|t66|vx400|mk99|d615|d763|el370|sl900|mp500|samu3|samu4|vx10|xda_|samu5|samu6|samu7|samu9|a615|b832|m881|s920|n210|s700|c-810|_h797|mob-x|sk16d|848b|mowser|s580|r800|471x|v120|rim8|c500foma:|160x|x160|480x|x640|t503|w839|i250|sprint|w398samr810|m5252|c7100|mt126|x225|s5330|s820|htil-g1|fly v71|s302|-x113|novarra|k610i|-three|8325rc|8352rc|sanyo|vx54|c888|nx250|n120|mtk |c5588|s710|t880|c5005|i;458x|p404i|s210|c5100|teleca|s940|c500|s590|foma|samsu|vx8|vx9|a1000|_mms|myx|a700|gu1100|bc831|e300|ems100|me701|me702m-three|sd588|s800|8325rc|ac831|mw200|brew |d88|htc\/|htc_touch|355x|m50|km100|d736|p-9521|telco|sl74|ktouch|m4u\/|me702|8325rc|kddi|phone|lg |sonyericsson|samsung|240x|x320|vx10|nokia|sony cmd|motorola|up.browser|up.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|pocket|kindle|mobile|psp|treo)/i',$user_agent));
				self::$mobile = true;
				self::$mobile_device = 'Mobile matched on piped preg_match';
				break;
			// O dispositivo mostra sinais de suporte a text/vnd.wap.wml ou application/vnd.wap.xhtml+xml
			case (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'],'text/vnd.wap.wml')>0 || strpos($_SERVER['HTTP_ACCEPT'],'application/vnd.wap.xhtml+xml')>0));
				self::$mobile = true;
				self::$mobile_device = '(WAP) Mobile matched on content accept header';
				break;
			// Dispositivo usa cabeçalho HTTP_X_WAP_PROFILE ou HTTP_PROFILE
			case (isset($_SERVER['HTTP_X_WAP_PROFILE'])||isset($_SERVER['HTTP_PROFILE']));
				self::$mobile = true;
				self::$mobile_device = '(WAP) Mobile matched on profile headers being set';
				break;
			// Verifica numa lista de outros agentes
			case (in_array(strtolower(substr($user_agent,0,4)),array('1207'=>'1207','3gso'=>'3gso','4thp'=>'4thp','501i'=>'501i','502i'=>'502i','503i'=>'503i','504i'=>'504i','505i'=>'505i','506i'=>'506i','6310'=>'6310','6590'=>'6590','770s'=>'770s','802s'=>'802s','a wa'=>'a wa','acer'=>'acer','acs-'=>'acs-','airn'=>'airn','alav'=>'alav','asus'=>'asus','attw'=>'attw','au-m'=>'au-m','aur '=>'aur ','aus '=>'aus ','abac'=>'abac','acoo'=>'acoo','aiko'=>'aiko','alco'=>'alco','alca'=>'alca','amoi'=>'amoi','anex'=>'anex','anny'=>'anny','anyw'=>'anyw','aptu'=>'aptu','arch'=>'arch','argo'=>'argo','bell'=>'bell','bird'=>'bird','bw-n'=>'bw-n','bw-u'=>'bw-u','beck'=>'beck','benq'=>'benq','bilb'=>'bilb','blac'=>'blac','c55/'=>'c55/','cdm-'=>'cdm-','chtm'=>'chtm','capi'=>'capi','cond'=>'cond','craw'=>'craw','dall'=>'dall','dbte'=>'dbte','dc-s'=>'dc-s','dica'=>'dica','ds-d'=>'ds-d','ds12'=>'ds12','dait'=>'dait','devi'=>'devi','dmob'=>'dmob','doco'=>'doco','dopo'=>'dopo','el49'=>'el49','erk0'=>'erk0','esl8'=>'esl8','ez40'=>'ez40','ez60'=>'ez60','ez70'=>'ez70','ezos'=>'ezos','ezze'=>'ezze','elai'=>'elai','emul'=>'emul','eric'=>'eric','ezwa'=>'ezwa','fake'=>'fake','fly-'=>'fly-','fly_'=>'fly_','g-mo'=>'g-mo','g1 u'=>'g1 u','g560'=>'g560','gf-5'=>'gf-5','grun'=>'grun','gene'=>'gene','go.w'=>'go.w','good'=>'good','grad'=>'grad','hcit'=>'hcit','hd-m'=>'hd-m','hd-p'=>'hd-p','hd-t'=>'hd-t','hei-'=>'hei-','hp i'=>'hp i','hpip'=>'hpip','hs-c'=>'hs-c','htc '=>'htc ','htc-'=>'htc-','htca'=>'htca','htcg'=>'htcg','htcp'=>'htcp','htcs'=>'htcs','htct'=>'htct','htc_'=>'htc_','haie'=>'haie','hita'=>'hita','huaw'=>'huaw','hutc'=>'hutc','i-20'=>'i-20','i-go'=>'i-go','i-ma'=>'i-ma','i230'=>'i230','iac'=>'iac','iac-'=>'iac-','iac/'=>'iac/','ig01'=>'ig01','im1k'=>'im1k','inno'=>'inno','iris'=>'iris','jata'=>'jata','java'=>'java','kddi'=>'kddi','kgt'=>'kgt','kgt/'=>'kgt/','kpt '=>'kpt ','kwc-'=>'kwc-','klon'=>'klon','lexi'=>'lexi','lg g'=>'lg g','lg-a'=>'lg-a','lg-b'=>'lg-b','lg-c'=>'lg-c','lg-d'=>'lg-d','lg-f'=>'lg-f','lg-g'=>'lg-g','lg-k'=>'lg-k','lg-l'=>'lg-l','lg-m'=>'lg-m','lg-o'=>'lg-o','lg-p'=>'lg-p','lg-s'=>'lg-s','lg-t'=>'lg-t','lg-u'=>'lg-u','lg-w'=>'lg-w','lg/k'=>'lg/k','lg/l'=>'lg/l','lg/u'=>'lg/u','lg50'=>'lg50','lg54'=>'lg54','lge-'=>'lge-','lge/'=>'lge/','lynx'=>'lynx','leno'=>'leno','m1-w'=>'m1-w','m3ga'=>'m3ga','m50/'=>'m50/','maui'=>'maui','mc01'=>'mc01','mc21'=>'mc21','mcca'=>'mcca','medi'=>'medi','meri'=>'meri','mio8'=>'mio8','mioa'=>'mioa','mo01'=>'mo01','mo02'=>'mo02','mode'=>'mode','modo'=>'modo','mot '=>'mot ','mot-'=>'mot-','mt50'=>'mt50','mtp1'=>'mtp1','mtv '=>'mtv ','mate'=>'mate','maxo'=>'maxo','merc'=>'merc','mits'=>'mits','mobi'=>'mobi','motv'=>'motv','mozz'=>'mozz','n100'=>'n100','n101'=>'n101','n102'=>'n102','n202'=>'n202','n203'=>'n203','n300'=>'n300','n302'=>'n302','n500'=>'n500','n502'=>'n502','n505'=>'n505','n700'=>'n700','n701'=>'n701','n710'=>'n710','nec-'=>'nec-','nem-'=>'nem-','newg'=>'newg','neon'=>'neon','netf'=>'netf','noki'=>'noki','nzph'=>'nzph','o2 x'=>'o2 x','o2-x'=>'o2-x','opwv'=>'opwv','owg1'=>'owg1','opti'=>'opti','oran'=>'oran','p800'=>'p800','pand'=>'pand','pg-1'=>'pg-1','pg-2'=>'pg-2','pg-3'=>'pg-3','pg-6'=>'pg-6','pg-8'=>'pg-8','pg-c'=>'pg-c','pg13'=>'pg13','phil'=>'phil','pn-2'=>'pn-2','pt-g'=>'pt-g','palm'=>'palm','pana'=>'pana','pire'=>'pire','pock'=>'pock','pose'=>'pose','psio'=>'psio','qa-a'=>'qa-a','qc-2'=>'qc-2','qc-3'=>'qc-3','qc-5'=>'qc-5','qc-7'=>'qc-7','qc07'=>'qc07','qc12'=>'qc12','qc21'=>'qc21','qc32'=>'qc32','qc60'=>'qc60','qci-'=>'qci-','qwap'=>'qwap','qtek'=>'qtek','r380'=>'r380','r600'=>'r600','raks'=>'raks','rim9'=>'rim9','rove'=>'rove','s55/'=>'s55/','sage'=>'sage','sams'=>'sams','sc01'=>'sc01','sch-'=>'sch-','scp-'=>'scp-','sdk/'=>'sdk/','se47'=>'se47','sec-'=>'sec-','sec0'=>'sec0','sec1'=>'sec1','semc'=>'semc','sgh-'=>'sgh-','shar'=>'shar','sie-'=>'sie-','sk-0'=>'sk-0','sl45'=>'sl45','slid'=>'slid','smb3'=>'smb3','smt5'=>'smt5','sp01'=>'sp01','sph-'=>'sph-','spv '=>'spv ','spv-'=>'spv-','sy01'=>'sy01','samm'=>'samm','sany'=>'sany','sava'=>'sava','scoo'=>'scoo','send'=>'send','siem'=>'siem','smar'=>'smar','smit'=>'smit','soft'=>'soft','sony'=>'sony','t-mo'=>'t-mo','t218'=>'t218','t250'=>'t250','t600'=>'t600','t610'=>'t610','t618'=>'t618','tcl-'=>'tcl-','tdg-'=>'tdg-','telm'=>'telm','tim-'=>'tim-','ts70'=>'ts70','tsm-'=>'tsm-','tsm3'=>'tsm3','tsm5'=>'tsm5','tx-9'=>'tx-9','tagt'=>'tagt','talk'=>'talk','teli'=>'teli','topl'=>'topl','hiba'=>'hiba','up.b'=>'up.b','upg1'=>'upg1','utst'=>'utst','v400'=>'v400','v750'=>'v750','veri'=>'veri','vk-v'=>'vk-v','vk40'=>'vk40','vk50'=>'vk50','vk52'=>'vk52','vk53'=>'vk53','vm40'=>'vm40','vx98'=>'vx98','virg'=>'virg','vite'=>'vite','voda'=>'voda','vulc'=>'vulc','w3c '=>'w3c ','w3c-'=>'w3c-','wapj'=>'wapj','wapp'=>'wapp','wapu'=>'wapu','wapm'=>'wapm','wig '=>'wig ','wapi'=>'wapi','wapr'=>'wapr','wapv'=>'wapv','wapy'=>'wapy','wapa'=>'wapa','waps'=>'waps','wapt'=>'wapt','winc'=>'winc','winw'=>'winw','wonu'=>'wonu','x700'=>'x700','xda2'=>'xda2','xdag'=>'xdag','yas-'=>'yas-','your'=>'your','zte-'=>'zte-','zeto'=>'zeto','acs-'=>'acs-','alav'=>'alav','alca'=>'alca','amoi'=>'amoi','aste'=>'aste','audi'=>'audi','avan'=>'avan','benq'=>'benq','bird'=>'bird','blac'=>'blac','blaz'=>'blaz','brew'=>'brew','brvw'=>'brvw','bumb'=>'bumb','ccwa'=>'ccwa','cell'=>'cell','cldc'=>'cldc','cmd-'=>'cmd-','dang'=>'dang','doco'=>'doco','eml2'=>'eml2','eric'=>'eric','fetc'=>'fetc','hipt'=>'hipt','http'=>'http','ibro'=>'ibro','idea'=>'idea','ikom'=>'ikom','inno'=>'inno','ipaq'=>'ipaq','jbro'=>'jbro','jemu'=>'jemu','java'=>'java','jigs'=>'jigs','kddi'=>'kddi','keji'=>'keji','kyoc'=>'kyoc','kyok'=>'kyok','leno'=>'leno','lg-c'=>'lg-c','lg-d'=>'lg-d','lg-g'=>'lg-g','lge-'=>'lge-','libw'=>'libw','m-cr'=>'m-cr','maui'=>'maui','maxo'=>'maxo','midp'=>'midp','mits'=>'mits','mmef'=>'mmef','mobi'=>'mobi','mot-'=>'mot-','moto'=>'moto','mwbp'=>'mwbp','mywa'=>'mywa','nec-'=>'nec-','newt'=>'newt','nok6'=>'nok6','noki'=>'noki','o2im'=>'o2im','opwv'=>'opwv','palm'=>'palm','pana'=>'pana','pant'=>'pant','pdxg'=>'pdxg','phil'=>'phil','play'=>'play','pluc'=>'pluc','port'=>'port','prox'=>'prox','qtek'=>'qtek','qwap'=>'qwap','rozo'=>'rozo','sage'=>'sage','sama'=>'sama','sams'=>'sams','sany'=>'sany','sch-'=>'sch-','sec-'=>'sec-','send'=>'send','seri'=>'seri','sgh-'=>'sgh-','shar'=>'shar','sie-'=>'sie-','siem'=>'siem','smal'=>'smal','smar'=>'smar','sony'=>'sony','sph-'=>'sph-','symb'=>'symb','t-mo'=>'t-mo','teli'=>'teli','tim-'=>'tim-','tosh'=>'tosh','treo'=>'treo','tsm-'=>'tsm-','upg1'=>'upg1','upsi'=>'upsi','vk-v'=>'vk-v','voda'=>'voda','vx52'=>'vx52','vx53'=>'vx53','vx60'=>'vx60','vx61'=>'vx61','vx70'=>'vx70','vx80'=>'vx80','vx81'=>'vx81','vx83'=>'vx83','vx85'=>'vx85','wap-'=>'wap-','wapa'=>'wapa','wapi'=>'wapi','wapp'=>'wapp','wapr'=>'wapr','webc'=>'webc','whit'=>'whit','winw'=>'winw','wmlb'=>'wmlb','xda-'=>'xda-',)));
				self::$mobile = true;
				self::$mobile_device = '(WAP) Mobile matched on in_array';
				break;
		}

		// tell adaptation services (transcoders and proxies) to not alter the content based on user agent as it's already being managed by this script
		// header('Cache-Control: no-transform'); // http://mobiforge.com/developing/story/setting-http-headers-advise-transcoding-proxies
		// header('Vary: User-Agent, Accept'); // http://mobiforge.com/developing/story/setting-http-headers-advise-transcoding-proxies

		return self::$mobile;
	}

	/**
	 *	\brief Informa se o usuário está usando um dispositivo móvel
	 */
	public static function getMobileDevice() {
		if (self::$mobile === NULL) {
			self::mobileDeviceDetect();
		}
		return (self::$mobile) ? (self::$mobile_device) : (self::$mobile);
	}

	/**
	 *	\brief Copyright do Framework
	 */
	public static function printCopyright() {
		if (ob_get_contents()) {
			ob_clean();
		}

		echo '<!DOCTYPE html>'."\n";
		echo '<html>';
		echo '<head>';
		echo '<title>FVAL PHP Framework for Web Applications - About</title>';
		echo '<style type="text/css">';
		echo 'body { padding:20px 40px;border:0;margin:0;background-color:#25567B;color:#fff;font-family:arial;font-size:11px;text-align:center; }';
		echo 'a, a:link, a:active, a:visited { text-decoration:none;color:#3F92D2; }';
		echo 'a:hover { color:#0B61A4; }';
		echo '.logo { display:block;padding:0;border:0;border-bottom:1px solid #fff;margin:0 auto;width:500px;height:70px;background:transparent url(data:image/png;base64,'.self::_img_logo().') no-repeat left top; }';
		echo '.logo a { display:block;color:#fff;padding:0;border:0;margin:0 0 0 150px;height:59px;line-height:59px;vertical-align:middle;font-size:150%;font-weight:bold; }';
		echo 'table { padding:0;border:0;margin:0 auto;cell-padding:0; }';
		echo 'tr { padding:0;border:0;margin:0; }';
		echo 'td { padding:0 5px 0 0;border:0;text-align:left;cell-padding:0; }';
		echo '</style>';
		echo '</head>';
		echo '<body>';
		echo '<h1 class="logo"><a href="http://www.fval.com.br">FVAL PHP Framework</a></h1>';
		echo '<p>Este projeto foi desenvolvido utilizando o <strong><a href="http://www.fval.com.br">FVAL</a> PHP Framework for Web Applications v'.self::VERSION.'</strong> para PHP.<br /></p>';
		echo '<p><strong>Este framework foi escrito por</strong></p><p>';
		echo 'Fernando Val - fernando at fval dot com dot br<br />';
		echo 'Lucas Cardozo - lucas dot cardozo at live dot com</p>';

		echo '<p><strong>Bibliotecas utilizadas nesse projeto</strong></p><table align="center">';
		$fv = array();
		$d = rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		if ($r = opendir($d)) {
			while (($f = readdir($r)) !== false) {
				if (filetype($d . $f) == 'file' && substr($f, -4) == '.php') {
					$fc = file($d . $f);
					$v = array('b'=>"",'v'=>"",'n'=>"");
					while (list(,$l) = each($fc)) {
						if (preg_match('/\*(\s*)[\\\\|@]brief[\s|\t]{1,}(.*)((\r)*(\n))$/', $l, $a)) {
							$v['b'] = trim($a[2]);
						}
						elseif (preg_match('/\*(\s*)\\\\version[\s|\t]{1,}(.*)((\r)*(\n))$/', $l, $a)) {
							$v['v'] = trim($a[2]);
						}
						elseif (preg_match('/(\s*)class[\s|\t]{1,}([a-zA-Z0-9_]+)(\s*)(extends)*(\s*)([a-zA-Z0-9_]*)(\s*)(\\{)/', $l, $a)) {
							$v['n'] = trim($a[2]);
							break;
						}
					}
					if ($v['n'] && $v['v'])
						$fv[$v['n']] = $v;
				}
			}
		}
		ksort($fv);
		foreach ($fv as $k => $v) {
			echo '<tr><td>'.$v['n'].'</td><td>'.$v['v'].($v['b']?'</td><td>'.$v['b']:"").'</td></tr>';
		}
		echo '</table>';

		echo '<p><strong>Classes Inclusas nesse framework</strong></p><p>';
		echo 'Smarty: the PHP compiling template engine v3.1.16 (c) 2008 New Digital Group, Inc.<br />';
		echo 'Sending e-mail messages via SMTP protocol v1.41 (c) 1999-2009 Manuel Lemos<br />';
		echo 'MIME E-mail message composing and sending v1.92 (c) 1999-2004 Manuel Lemos<br />';
		echo 'MIME E-mail message composing and sending using Sendmail v1.18 (c) 1999-2004 Manuel Lemos<br />';
		echo 'MIME E-mail message composing and sending via SMTP v1.36 (c) 1999-2004 Manuel Lemos<br />';
		echo 'Simple Authentication and Security Layer client v1.11 (c) 2001-2005 Manuel Lemos<br />';
		// echo 'NuSOAP - Web Services Toolkit for PHP v1.123 (c) 2002 NuSphere Corporation<br />';
		echo 'FeedCreator v1.7.2 (c) Kai Blankenhorn<br />';
		echo '</p></body>';
		echo '</html>';

		exit(0);
	}

	private static function _img_logo() {
		return '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';
	}
}
