<?php
/**	\file
 *  FVAL PHP Framework for Web Applications.
 *
 *  \copyright  Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright  Copyright (c) 2007-2015 Fernando Val\n
 *  \copyright  Copyright (c) 2009-2013 Lucas Cardozo
 *
 *  \brief      Script da classe cerne do framework
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    2.0.53
 *  \author     Fernando Val  - fernando.val@gmail.com
 *  \author     Lucas Cardozo - lucas.cardozo@gmail.com
 *  \ingroup    framework
 */
namespace FW;

/**
 *  \brief Classe cerne do framework.
 *  
 *  Esta classe é estática e invocada automaticamente pelo framework.
 */
class Kernel
{
    /// Versão do framework
    const VERSION = '3.3.1';

    /// Kernel constants
    const PATH_CLASS = 'CLASS';
    const PATH_CONFIGURATION = 'CONFIGURATION';
    const PATH_CONTROLLER = 'CONTROLLER';
    const PATH_LIBRARY = 'LIBRARY';
    const PATH_ROOT = 'ROOT';
    const PATH_SYSTEM = 'SYSTEM';
    const PATH_VENDOR = 'VENDOR';

    /// Start time
    private static $startime = null;
    /// Array com informações de debug
    private static $debug = [];
    /// Determina se o usuário está usando dispositivo móvel (Deprecated. Will be removed.)
    private static $mobile = null;
    /// Determina o tipo de dispositivo móvel (Deprecated. Will be removed.)
    private static $mobile_device = null;
    /// Determina o root de controladoras
    private static $controller_root = [];
    /// Caminho do namespace do controller
    private static $controller_namespace = null;

    /// System environment
    private static $environment = '';
    /// System name
    private static $name = 'Sistem Name';
    /// System version
    private static $version = [0, 0, 0];
    /// System path
    private static $paths = [];
    /// System charset
    private static $charset = 'UTF-8';

    /**
     *  \brief Start system environment.
     */
    public static function initiate($sysconf, $startime = null)
    {
        ini_set('date.timezone', $sysconf['TIMEZONE']);

        self::$startime = is_null($startime) ? microtime(true) : $startime;
        self::systemName($sysconf['SYSTEM_NAME']);
        self::systemVersion($sysconf['SYSTEM_VERSION']);
        self::charset($sysconf['CHARSET']);
        self::environment(
            $sysconf['ACTIVE_ENVIRONMENT'],
            isset($sysconf['ENVIRONMENT_ALIAS']) ? $sysconf['ENVIRONMENT_ALIAS'] : [],
            isset($sysconf['ENVIRONMENT_VARIABLE']) ? $sysconf['ENVIRONMENT_VARIABLE'] : ''
        );

        self::path(self::PATH_LIBRARY, isset($sysconf['LIBRARY_PATH']) ? $sysconf['LIBRARY_PATH'] : realpath(dirname(__FILE__)));
        self::path(self::PATH_SYSTEM, isset($sysconf['SYSTEM_PATH']) ? $sysconf['SYSTEM_PATH'] : realpath(self::path(self::PATH_LIBRARY).DIRECTORY_SEPARATOR.'..'));
        self::path(self::PATH_ROOT, isset($sysconf['ROOT_PATH']) ? $sysconf['ROOT_PATH'] : realpath(self::path(self::PATH_SYSTEM).DIRECTORY_SEPARATOR.'..'));
        self::path(self::PATH_CONTROLLER, isset($sysconf['CONTROLER_PATH']) ? $sysconf['CONTROLER_PATH'] : realpath(self::path(self::PATH_SYSTEM).DIRECTORY_SEPARATOR.'controllers'));
        self::path(self::PATH_CLASS, isset($sysconf['CLASS_PATH']) ? $sysconf['CLASS_PATH'] : realpath(self::path(self::PATH_SYSTEM).DIRECTORY_SEPARATOR.'classes'));
        self::path(self::PATH_CONFIGURATION, isset($sysconf['CONFIG_PATH']) ? $sysconf['CONFIG_PATH'] : realpath(self::path(self::PATH_SYSTEM).DIRECTORY_SEPARATOR.'conf'));
        self::path(self::PATH_VENDOR, isset($sysconf['3RDPARTY_PATH']) ? $sysconf['3RDPARTY_PATH'] : realpath(self::path(self::PATH_SYSTEM).DIRECTORY_SEPARATOR.'other'));
    }

    /**
     *  \brief Return the system runtime until now.
     */
    public static function runTime()
    {
        return number_format(microtime(true) - self::$startime, 6);
    }

    /**
     *  \brief The system environment.
     *  
     *  \param string $env - if defined, set the system environment
     *  \return A string containing the system environment
     */
    public static function environment($env = null, $alias = [], $envar = '')
    {
        if (!is_null($env)) {
            // Define environment by host?
            if (empty($env)) {
                if (!empty($envar)) {
                    $env = getenv($envar);
                }

                $env = empty($env) ? URI::http_host() : $env;
                if (empty($env)) {
                    $env = 'unknown';
                }

                // Verify if has an alias for host
                if (is_array($alias) && count($alias)) {
                    foreach ($alias as $host => $as) {
                        if (preg_match('/^'.$host.'$/', $env)) {
                            $env = $as;
                            break;
                        }
                    }
                }
            }

            self::$environment = $env;
        }

        return self::$environment;
    }

    /**
     *  \brief The system name.
     *  
     *  \param string $name - if defined, set the system name
     *  \return A string containing the system name
     */
    public static function systemName($name = null)
    {
        if (!is_null($name)) {
            self::$name = $name;
        }

        return self::$name;
    }

    /**
     *  \brief The system version.
     *  
     *  \param $major - if defined, set the major part of the system version. Can be an array with all parts.
     *  \param $minor - if defined, set the minor part of the system version
     *  \param $build - if defined, set the build part of the system version
     *  \return A string containing the system version
     */
    public static function systemVersion($major = null, $minor = null, $build = null)
    {
        if (!is_null($major) && !is_null($minor) && !is_null($build)) {
            self::$version = [$major, $minor, $build];
        } elseif (!is_null($major) && !is_null($minor)) {
            self::$version = [$major, $minor];
        } elseif (!is_null($major)) {
            self::$version = [$major];
        }

        return is_array(self::$version) ? implode('.', self::$version) : self::$version;
    }

    /**
     *  \brief The system charset.
     *  
     *  Default UTF-8
     *  
     *  \param string $charset - if defined, set the system charset
     *  \return A string containing the system charset
     */
    public static function charset($charset = null)
    {
        if (!is_null($charset)) {
            self::$charset = $charset;
            ini_set('default_charset', $charset);
            // Send the content-type and charset header
            header('Content-Type: text/html; charset='.$charset, true);
            if (phpversion() < '5.6') {
                ini_set('mbstring.internal_encoding', $charset);
            }
        }

        return self::$charset;
    }

    /**
     *  \brief A path of the system.
     *  
     *  \param string $component - the component constant
     *  \param string $path - if defined, change the path of the component
     *  \return A string containing the path of the component
     */
    public static function path($component, $path = null)
    {
        if (!is_null($path)) {
            self::$paths[ $component ] = $path;
        }

        return isset(self::$paths[ $component ]) ? self::$paths[ $component ] : '';
    }

    /**
     *  \brief Pega ou seta o root de controladoras.
     *  
     *  \param (array)$controller_root - ae definido, altera o root de controladoras
     *  \return Retorna um array contendo o root de controladoras
     */
    public static function controllerRoot($controller_root = null)
    {
        if (!is_null($controller_root)) {
            self::$controller_root = $controller_root;
        }

        return self::$controller_root;
    }

    /**
     *	\brief Define o namespace da controller a ser carregada.
     *
     *	\param string $controller
     */
    public static function controllerNamespace($controller = null)
    {
        if (!is_null($controller) && file_exists($controller)) {
            $controller = pathinfo($controller);
            $controller = str_replace(self::path(self::PATH_CONTROLLER), '', $controller['dirname']);
            $controller = str_replace(DIRECTORY_SEPARATOR, '/', $controller);
            self::$controller_namespace = trim($controller, '/');
        }

        return self::$controller_namespace;
    }

    /**
     *	\brief Põe uma informação na janela de debug.
     */
    public static function debug($txt, $name = '', $highlight = true, $revert = true)
    {
        $debug = [
            memory_get_usage(true),
            $name,
            $highlight,
            $txt,
            debug_backtrace(),
        ];
        if ($revert) {
            array_unshift(self::$debug, $debug);
        } else {
            self::$debug[] = $debug;
        }
    }

    /**
     *	\brief Imprime o bloco de debug.
     *
     *	\return void
     */
    public static function debugPrint()
    {
        if (!defined('STDIN') && Configuration::get('system', 'debug') == true && !Configuration::get('system', 'sys_ajax')) {
            $size = memory_get_peak_usage(true);
            $unit = ['b', 'KB', 'MB', 'GB', 'TB', 'PB'];
            $memoria = round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).' '.$unit[$i];
            unset($unit, $size);

            self::debug('Runtime execution time: '.self::runTime().' seconds'."\n".'Maximum memory consumption: '.$memoria, '', true, false);
            unset($memoria);

            $conteudo = ob_get_contents();
            ob_clean();

            $debugTemplate = dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR.'debug_template.html';
            if (file_exists($debugTemplate) && $htmlDebug = file_get_contents($debugTemplate)) {
                $htmlDebug = preg_replace(['/<!-- DEBUG CONTENT \(.+\) -->/mu', '~<!--.*?-->~s', '!/\*.*?\*/!s', "/\n\s+/", "/\n(\s*\n)+/", "!\n//.*?\n!s", "/\n\}(.+?)\n/", "/\}\s+/", "/,\n/", "/>\n/", "/\{\s*?\n/", "/\}\n/", "/;\n/"], [self::getDebugContent(), '', '', "\n", "\n", "\n", "}\\1\n", '}', ', ', '>', '{', '} ', ';'], $htmlDebug);
            }

            if (preg_match('/<\/body>/', $conteudo)) {
                echo preg_replace('/<\/body>/', $htmlDebug.'</body>', $conteudo);
            } else {
                echo preg_replace('/^(.*?)$/', '<script type="text/javascript" src="'.URI::buildURL([Configuration::get('uri', 'js_dir')], [], true, 'static').'/jquery.js"></script>'.$htmlDebug.'\\1', $conteudo);
            }
        }
    }

    /**
     *	\brief Junta o conteúdo do array de debug numa string com separador visual.
     *
     *	\return Retorna uma string contendo os dados capturados em debug
     */
    public static function getDebugContent()
    {
        $return = [];
        foreach (self::$debug as $debug) {
            $id = 'debug_'.mt_rand().str_replace('.', '', current(explode(' ', microtime())));

            $unit = ['b', 'KB', 'MB', 'GB', 'TB', 'PB'];
            $memoria = round($debug[0] / pow(1024, ($i = floor(log($debug[0], 1024)))), 2).' '.$unit[$i];

            $return[] = '
			<div class="debug_info">
				<table width="100%" border="0" cellspacing="0" cellpadding="0" align="left">
				  <thead>
					<th colspan="2" align="left">'.($debug[1] ? $debug[1].' - ' : '').'Allocated Memory: '.$memoria.'</th>
				  </thead>
				  <tr>
					<td width="50%" valign="top"> '.($debug[2] ? self::print_rc($debug[3]) : $debug[3]).'</td>
					<td width="50%" valign="top">
						<a href="javascript:;" onclick="var obj=$(\'#'.$id.'\').toggle()">Debug BackTrace</a>
						<div id="'.$id.'" style="display:none">'.self::makeDebugBacktrace($debug[4]).'</div></td>
				  </tr>
				</table>
			</div>
			';
        }

        return implode('<hr />', $return);
    }

    /**
     *	\brief Imprime os detalhes de uma variável em cores.
     *
     *	\param[in] (variant) $par - variável
     *	\param[in] (bool) $return - sem utilização
     *	\return Retorna uma string HTML
     */
    public static function print_rc($par, $return = false)
    {
        if (is_object($par)) {
            if (method_exists($par, '__toString')) {
                return str_replace('&lt;?php', '', str_replace('?&gt;', '', highlight_string('<?php '.var_export($par->__toString(), true).' ?>', true))).
                (($par instanceof DBSelect || $par instanceof DBInsert || $par instanceof DBUpdate || $par instanceof DBDelete) ? '<br />'.str_replace('&lt;?php', '', str_replace('?&gt;', '', highlight_string('<?php '.var_export($par->getAllValues(), true).' ?>', true))) : '');
            } else {
                return (PHP_SAPI === 'cli' || defined('STDIN')) ? print_r($par, true) : '<pre>'.print_r($par, true).'</pre>';
            }
        } else {
            return str_replace('&lt;?php', '', str_replace('?&gt;', '',
                highlight_string('<?php '.print_r($par, true).' ?>', true)
            ));
        }
    }

    /**
     *	\brief Monta o texto do debug backtrace.
     *
     *	\param[in] (array) $debug array com o backtrace gerado
     */
    public static function makeDebugBacktrace($debug = null)
    {
        if (!is_array($debug)) {
            $debug = debug_backtrace();
        }
        array_shift($debug);

        $aDados = [];

        foreach ($debug as $value) {
            if (empty($value['line']) || strpos($value['file'], 'Errors.php') > 0) {
                continue;
            }

            $linhas = explode('<br />', str_replace('<br /></span>', '</span><br />', highlight_string(file_get_contents($value['file']), true)));
            $aDados[] = [
                'arquivo'        => $value['file'],
                'linha'          => $value['line'],
                'args'           => isset($value['args']) ? $value['args'] : 'Sem argumentos passados',
                'conteudo_linha' => trim(preg_replace('/^(&nbsp;)+/', '', $linhas[ $value['line'] - 1 ])),
            ];
        }

        $tr = 0;
        $saida = '    <ul style="font-family:Arial, Helvetica, sans-serif; font-size:12px">';
        $i = 0;
        $li = 0;

        foreach ($aDados as $key => $backtrace) {
            if ($backtrace['linha'] > 0) {
                $backtrace['conteudo_linha'] = preg_replace('/^<\/span>/', '', trim($backtrace['conteudo_linha']));
                if (!preg_match('/<\/span>$/', $backtrace['conteudo_linha'])) {
                    $backtrace['conteudo_linha'] .= '</span>';
                }

                $linha = sprintf('[%05d]', $backtrace['linha']);
                $saida .= '      <li style="margin-bottom: 5px; '.($li + 1 < count($aDados) ? 'border-bottom:1px dotted #000; padding-bottom:5px' : '').'">'
                       .'        <span style="'.($i == 1 ? ' color:#F00; ' : '').'"><b>'.$linha.'</b>&nbsp;<b>'.$backtrace['arquivo'].'</b></span><br />'
                       .'        '.$backtrace['conteudo_linha'];

                if (count($backtrace['args'])) {
                    $id = 'args_'.mt_rand().str_replace('.', '', current(explode(' ', microtime())));
                    $saida .= '        <br />'."\n"
                           .'        <a href="javascript:;" onClick="var obj=$(\'#'.$id.'\').toggle()" style="color:#06c; margin:3px 0">arguments passed to function</a>'
                           .'        '.(is_array($backtrace['args']) ? '<div id="'.$id.'" style="display:none">'.self::print_rc($backtrace['args'], true).'</div>' : $backtrace['args']);
                }
                $saida .= '      </li>';
                $li++;
            }
            $tr++;
        }

        return $saida.'</ul>';
    }

    /**
     *	\brief Converte um array multidimensional no objeto stdClass.
     *
     *	\param[in] $array (mixed) array a ser convertido
     *	\return Retorna um objeto stdClasse
     */
    public static function arrayToObject($array)
    {
        if (!is_array($array)) {
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

        return false;
    }

    /**
     *	\brief Converte um objeto num array multidimensional.
     *
     *	\param[in] $object (mixed) objeto a ser convertido
     *	\return Retorna um array
     */
    public static function objectToArray($object)
    {
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
     *  \brief Verifica se o usuário está usando um browser de dispositivo móvel.
     *  
     *  This method was deprecated.
     *  
     *  Use sinergi/browser-detector library from Chris Schuld & Gabriel Bull
     *  
     *  \see https://packagist.org/packages/sinergi/browser-detector
     *  \deprecated
     *  \warning This method was removed.
     */
    private static function mobileDeviceDetect()
    {
        throw new \Exception('Deprecated method');
    }

    /**
     *	\brief Informa se o usuário está usando um dispositivo móvel.
     *  
     *  This method was deprecated.
     *  
     *  Use sinergi/browser-detector library from Chris Schuld & Gabriel Bull
     *  
     *  \see https://packagist.org/packages/sinergi/browser-detector
     *  \deprecated
     *  \warning This method was removed.
     */
    public static function getMobileDevice()
    {
        throw new \Exception('Deprecated method');
    }

    /**
     *  \brief Copyright page.
     */
    public static function printCopyright()
    {
        if (ob_get_contents()) {
            ob_clean();
        }

        echo '<!DOCTYPE html>'."\n";
        echo '<html>';
        echo '<head>';
        echo '<title>FVAL PHP Framework for Web Applications - About</title>';
        echo '<style type="text/css">';
        echo 'body { padding:20px 40px;border:0;margin:0;background-color:#0F3E06;color:#fff;font-family:arial;font-size:11px;text-align:center; }';
        echo 'a, a:link, a:active, a:visited { text-decoration:none;color:#3F92D2; }';
        echo 'a:hover { color:#0B61A4; }';
        echo '.logo { display:block;padding:0 0 5px 0;border:0;border-bottom:1px solid #fff;margin:0;height:50px; }';
        echo '.logo a { display:block;color:#fff;padding:0;border:0;margin:0;height:50px;line-height:50px;vertical-align:middle;font-size:150%;font-weight:bold; }';
        echo '.logo img { vertical-align:middle; }';
        echo '.logo span { color:#FF9900; }';
        echo '.class { color:#F9F7BD; }';
        echo '.fw { color:#A2A2A2; }';
        echo '.slash { color:#888; }';
        echo '.version { color:#62E44C; }';
        echo '.description { color:#CCC; }';
        echo 'table { padding:0;border:0;margin:0 auto;cell-padding:0; }';
        echo 'tr { padding:0;border:0;margin:0; }';
        echo 'td { padding:0 5px 0 0;border:0;text-align:left;cell-padding:0; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<h1 class="logo"><a href="https://github.com/fernandoval/FVAL-PHP-Framework"><img src="data:image/png;base64,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" title=""> <span>FVAL</span> PHP Framework</a></h1>';
        echo '<p>Release <span class="version">'.self::VERSION.'</span></strong>.<br /></p>';

        echo '<p>A micro framework for smart <a href="http://php.net">PHP</a> developers.</p>';

        echo '<p class="description">KISS is our philosophy. KISS is good. KISS is a principle. So write codes with KISS in your mind and <a href="https://en.wikipedia.org/wiki/KISS_principle">keep it simple, silly</a>.</p>';

        echo '<p><strong>List of the library classes:</strong></p><table align="center">';
        $d = rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        foreach (self::_list_classes($d, 'FW') as $k => $v) {
            echo '<tr><td>'.str_replace('\\', '<span class="slash">\\</span>', str_replace('FW\\', '<span class="fw">FW</span>\\', str_replace('class ', '<span class="class">class</span> ', str_replace('interface ', '<span class="class">interface</span> ', $v['n'])))).'</td><td class="version">'.$v['v'].($v['b'] ? '</td><td class="description">'.$v['b'] : '').'</td></tr>';
        }
        echo '</table>';

        echo '<p><strong>This framework was created by</strong></p><p>';
        echo 'Fernando Val - fernando at fval dot com dot br<br />';
        echo 'Lucas Cardozo - lucas dot cardozo at live dot com</p>';

        echo '<p class="description">This framework is Open Source and distributed under <a href="https://opensource.org/licenses/MIT">MIT</a> license.</p>';

        echo '</p><p><a href="http://fval.com.br"><img src="data:image/png;base64,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" title="Powered by FVAL"></a></p>';

        echo '</body>';
        echo '</html>';

        exit(0);
    }

    private static function _list_classes($d, $ns)
    {
        $fv = [];
        if ($r = opendir($d)) {
            while (($f = readdir($r)) !== false) {
                if (filetype($d.$f) == 'file' && substr($f, -4) == '.php') {
                    $fc = file($d.$f);
                    $v = ['b' => '', 'v' => '', 'n' => ''];
                    while (list(, $l) = each($fc)) {
                        if (preg_match('/\*(\s*)[\\\\|@]brief[\s|\t]{1,}(.*)((\r)*(\n))$/', $l, $a)) {
                            $v['b'] = trim($a[2]);
                        } elseif (preg_match('/\*([\s|\t]*)\\\\version[\s|\t]{1,}(.*)((\r)*(\n))$/', $l, $a)) {
                            $v['v'] = trim($a[2]);
                        } elseif (preg_match('/^(class|interface)[\s|\t]{1,}([a-zA-Z0-9_]+)(\s*)(extends)*(\s*)([a-zA-Z0-9_]*)(\s*)(\\{*)/', $l, $a)) {
                            $v['n'] = $a[1].' '.$ns.'\\'.trim($a[2]);
                            break;
                        }
                    }
                    if ($v['n'] && $v['v']) {
                        $fv[$v['n']] = $v;
                    }
                } elseif (!in_array($f, ['.', '..']) && filetype($d.$f) == 'dir') {
                    $fv = array_merge($fv, self::_list_classes($d.$f.DIRECTORY_SEPARATOR, $ns.'\\'.$f));
                }
            }
        }
        ksort($fv);

        return $fv;
    }
}
