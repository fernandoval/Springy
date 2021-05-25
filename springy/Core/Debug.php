<?php

/**
 * Framework debug.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version    1.0.4.9
 */

namespace Springy\Core;

use Springy\Configuration;
use Springy\Kernel;

/**
 * Framework debug class.
 */
class Debug
{
    /// Debug information array
    private static $debug = [];

    /**
     *  @brief Add a information to the debug window.
     */
    public static function add($txt, $name = '', $highlight = true, $revert = true)
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

            return;
        }

        self::$debug[] = $debug;
    }

    /**
     *  @brief Get the debug backtrace.
     *
     *  @param array $debug array with the backtrace or null to get from the system.
     *  @param int $limit this parameter can be used to limit the number of stack frames loaded.
     *      Setting zero value (0) it catch all stack frames.
     *      By default (limit=10) it catch 10 stack frames.
     */
    public static function backtrace($debug = null, $limit = 10)
    {
        if (!is_array($debug)) {
            $debug = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $limit);
        }
        array_shift($debug);

        $aDados = [];

        foreach ($debug as $value) {
            if (empty($value['line']) || strpos($value['file'], 'Errors.php') > 0) {
                continue;
            }

            $linhas = explode('<br />', str_replace('<br /></span>', '</span><br />', highlight_string(file_get_contents($value['file']), true)));
            $aDados[] = [
                'file'    => $value['file'],
                'line'    => $value['line'],
                'args'    => isset($value['args']) ? $value['args'] : [],
                'content' => trim(preg_replace('/^(&nbsp;)+/', '', $linhas[$value['line'] - 1])),
            ];
        }

        $result = '<ul style="font-family:Arial, Helvetica, sans-serif; font-size:12px">';
        $htmlLI = 0;

        foreach ($aDados as $backtrace) {
            if ($backtrace['line'] > 0) {
                $backtrace['content'] = preg_replace('/^<\/span>/', '', trim($backtrace['content']));
                if (!preg_match('/<\/span>$/', $backtrace['content'])) {
                    $backtrace['content'] .= '</span>';
                }

                $line = sprintf('[%05d]', $backtrace['line']);
                $result .= '<li style="margin-bottom: 5px; ' . ($htmlLI + 1 < count($aDados) ? 'border-bottom:1px dotted #000; padding-bottom:5px' : '') . '"><span><b>' . $line . '</b>&nbsp;<b>' . $backtrace['file'] . '</b></span><br />' . $backtrace['content'];

                if (count($backtrace['args'])) {
                    $aid = 'args_' . mt_rand() . str_replace('.', '', current(explode(' ', microtime())));
                    $result .= '<br /><a href="javascript:;" onClick="var obj=$(\'#' . $aid . '\').toggle()" style="color:#06c; margin:3px 0">arguments passed to function</a>' . (is_array($backtrace['args']) ? '<div id="' . $aid . '" style="display:none">' . self::print_rc($backtrace['args']) . '</div>' : $backtrace['args']);
                }
                $result .= '      </li>';
                $htmlLI++;
            }
        }

        return $result . '</ul>';
    }

    /**
     *  @brief Get the debug content in the string format.
     *
     *  @return string Retorna uma string contendo os dados capturados em debug.
     */
    public static function get()
    {
        $return = [];
        foreach (self::$debug as $debug) {
            $did = 'debug_' . mt_rand() . str_replace('.', '', current(explode(' ', microtime())));

            $unit = ['b', 'KB', 'MB', 'GB', 'TB', 'PB'];
            $memoria = round($debug[0] / pow(1024, ($idx = floor(log($debug[0], 1024)))), 2) . ' ' . $unit[$idx];

            $return[] = '<div class="Spring-Debug-Info">' .
                        '<p>' . ($debug[1] ? $debug[1] . ' - ' : '') . 'Allocated Memory: ' . $memoria . '</p>' .
                        '<div> ' . ($debug[2] ? self::print_rc($debug[3]) : $debug[3]) . '</div>' .
                        '<div>' .
                        '<div class="Spring-Debug-Backtrace-Button"><a href="javascript:;" onclick="var obj=$(\'#' . $did . '\').toggle()">open debug backtrace</a></div>' .
                        '<div class="Spring-Debug-Backtrace-Data" id="' . $did . '" style="display:none" class="Spring-Debug-Backtrace">' . self::backtrace($debug[4]) . '</div></div>' .
                        '</div>';
        }

        return implode('<hr />', $return);
    }

    /**
     *  @brief Print out the debug content.
     *
     *  @return void
     */
    public static function printOut()
    {
        if (!defined('STDIN') && Configuration::get('system', 'debug') == true && !Configuration::get('system', 'sys_ajax')) {
            $size = memory_get_peak_usage(true);
            $unit = ['b', 'KB', 'MB', 'GB', 'TB', 'PB'];
            $mem = round($size / pow(1024, ($idx = floor(log($size, 1024)))), 2) . ' ' . $unit[$idx];
            unset($unit, $size);

            self::add(
                'Runtime execution time: ' .
                Kernel::runTime() .
                ' seconds' . "\n" .
                'Maximum memory consumption: ' . $mem,
                '',
                true,
                false
            );
            unset($mem);

            $content = ob_get_contents();
            ob_clean();
            ob_start();

            $debugTemplate = dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . 'debug_template.html';
            if (file_exists($debugTemplate) && $htmlDebug = file_get_contents($debugTemplate)) {
                $htmlDebug = preg_replace(['/<!-- DEBUG CONTENT \(.+\) -->/mu', '~<!--.*?-->~s', '!/\*.*?\*/!s', "/\n\s+/", "/\n(\s*\n)+/", "!\n//.*?\n!s", "/\n\}(.+?)\n/", "/\}\s+/", "/,\n/", "/>\n/", "/\{\s*?\n/", "/\}\n/", "/;\n/"], [self::get(), '', '', "\n", "\n", "\n", "}\\1\n", '}', ', ', '>', '{', '} ', ';'], $htmlDebug);
            }

            if (preg_match('/<\/body>/', $content)) {
                echo preg_replace('/<\/body>/', $htmlDebug . '</body>', $content);

                return;
            }

            echo preg_replace(
                '/^(.*?)$/',
                '<script src="https://cdn.jsdelivr.net/npm/jquery@3.4.1/dist/jquery.min.js"></script>' .
                $htmlDebug . '\\1',
                $content
            );
        }
    }

    /**
     *  @brief Imprime os detalhes de uma variável em cores.
     *
     *  @param variant $par - variável
     *
     *  @return string Retorna uma string HTML.
     */
    public static function print_rc($par)
    {
        if (is_object($par)) {
            if (method_exists($par, '__toString')) {
                return str_replace('&lt;?php&nbsp;', '', str_replace('&nbsp;?&gt;', '', highlight_string('<?php ' . var_export($par->__toString(), true), true)));
            }

            return (PHP_SAPI === 'cli' || defined('STDIN')) ? print_r($par, true) : '<pre>' . print_r($par, true) . '</pre>';
        }

        return str_replace('&lt;?php&nbsp;', '', str_replace('&nbsp;?&gt;', '', highlight_string('<?php ' . print_r($par, true), true)));
    }
}
