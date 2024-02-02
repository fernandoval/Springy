<?php

/**
 * Framework debug.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version    1.1.0
 */

namespace Springy\Core;

use Springy\Configuration;
use Springy\Kernel;

/**
 * Framework debug class.
 */
class Debug
{
    /** @var array Debug information array */
    private static $debug = [];

    /**
     * Add a information to the debug window.
     */
    public static function add($txt, $name = '', $highlight = true, $revert = true)
    {
        $debug = [
            memory_get_usage(true),
            Kernel::runTime(),
            $txt,
            debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3),
            $name,
            $highlight,
        ];
        if ($revert) {
            array_unshift(self::$debug, $debug);

            return;
        }

        self::$debug[] = $debug;
    }

    /**
     * Parses the backtrace to HTML string.
     *
     * @param array $debug
     *
     * @return string
     */
    private static function backtrace(array $backtrace = []): string
    {
        if (empty($backtrace)) {
            return '';
        }

        $result = '<ul>';
        $translated = self::translateBacktrace($backtrace);

        // Build the backtrace HTML
        foreach ($translated as $trace) {
            $trace['content'] = preg_replace('/^<\/span>/', '', trim($trace['content']));
            if (!preg_match('/<\/span>$/', $trace['content'])) {
                $trace['content'] .= '</span>';
            }

            $line = sprintf('[%05d]', $trace['line']);
            $result .= '<li><p><strong>' . $line . '</strong> '
                . $trace['file'] . '</p><div class="springy-debug-backtrace-content">'
                . $trace['content'] . '</div>';

            if (count($trace['args'])) {
                $result .= '<ul class="springy-debug-backtrace-args">';

                foreach ($trace['args'] as $arg) {
                    $result .= '<li>' . self::highligh($arg) . '</li>';
                }

                $result .= '</ul>';
            }

            $result .= '</li>';
        }

        return $result . '</ul>';
    }

    /**
     * Formats a ver_dump to a beauty output.
     *
     * @param mixed $data
     *
     * @return string
     */
    private static function dumpData($data): string
    {
        ob_start();
        var_dump($data);
        $xpto = ob_get_clean();
        $export = $xpto;
        $export = preg_replace('/\s*\bNULL\b/m', 'null', $export); // Cleanup NULL
        $export = preg_replace('/\s*\bbool\((true|false)\)/m', '$1', $export); // Cleanup booleans
        $export = preg_replace('/\s*\bint\((\d+)\)/m', '$1', $export); // Cleanup integers
        $export = preg_replace('/\s*\bfloat\(([\d.e-]+)\)/mi', '$1', $export); // Cleanup floats
        $export = preg_replace('/\s*\bstring\(\d+\) /m', '', $export); // Cleanup strings
        $export = preg_replace('/object\((\w+)\)(#\d+) (\(\d+\))/m', '$1', $export); // Cleanup objects definition
        //
        $export = preg_replace('/=>\s*/m', ' => ', $export); // No new line between array/object keys and properties
        $export = preg_replace('/\[([\w": ]+)\]/', '$1 ', $export); // remove square brackets in array/object keys
        // remove square brackets in array/object keys
        // $export = preg_replace('/\[([\w": ]+)\]/', ', $1 ', $export);
        // remove first coma in array/object properties listing
        // $export = preg_replace('/([{(]\s+), /', '$1  ', $export);
        $export = preg_replace('/\{\s+\}/m', '{}', $export);
        $export = preg_replace('/\s+$/m', '', $export); // Trim end spaces/new line

        $export = preg_replace('/(array\(\d+\) ){([^}]+)}/m', '$1[$2]', $export); // Cleanup objects definition
        $export = preg_replace('/(.+=>.+)/m', '$1,', $export); // Cleanup objects definition

        return $export;
    }

    /**
     * Formats a debug data to HTML output.
     *
     * @param array $debug
     *
     * @return string
     */
    private static function formater(array $debug): string
    {
        $btcount = $debug[4] > 0 ? 'last ' . $debug[4] : 'all';
        $cdiv = '</div>';

        return '<div class="springy-debug-info">'
            . '<div class="springy-debug-time"><strong>Time:</strong> '
            . sprintf('%.6f', $debug[1])
            . ' s | <strong>Memory:</strong> '
            . memory_string($debug[0])
            . '  <a href="javascript:;" class="springy-debug-remove" title="Delete"></a></div>'
            . '<div class="springy-debug-value">'
            . self::highligh($debug[2])
            . $cdiv
            . (
                $debug[4] > 0
                ? '<a class="spring-debug-backtrace-btn">Backtrace ('
                    . $btcount
                    . ') <i class="springy-arrow down"></i></a>'
                    . '<div class="spring-debug-backtrace-data">'
                    . self::backtrace($debug[3])
                    . $cdiv
                : ''
            )
            . $cdiv;
    }

    /**
     * Get the debug content in the string format.
     *
     * @return string Retorna uma string contendo os dados capturados em debug.
     */
    public static function get()
    {
        return array_reduce(
            self::$debug,
            fn (string $carry, array $item) => $carry . self::formater($item),
            ''
        );
    }

    /**
     * Hightlights the data details.
     *
     * @param mixed $data
     *
     * @return string
     */
    private static function highligh($data): string
    {
        $export = self::dumpData($data);

        if (php_sapi_name() === 'cli') {
            return $export;
        }

        return str_replace(
            '&lt;?php&nbsp;',
            '',
            str_replace(
                '&nbsp;?&gt;',
                '',
                highlight_string('<?php ' . $export, true)
            )
        );
    }

    /**
     * Print out the debug content.
     *
     * @return void
     */
    public static function printOut()
    {
        if (
            defined('STDIN')
            || Configuration::get('system.debug') !== true
            || Configuration::get('system.sys_ajax')
        ) {
            return;
        }

        self::add(
            'Execution time: '
            . Kernel::runTime()
            . ' seconds' . LF
            . 'Maximum memory used: ' . memory_string(memory_get_peak_usage(true)),
            '',
            true,
            false
        );

        $content = ob_get_contents();

        if (!preg_match('/<\/body>/', $content)) {
            return;
        }

        ob_clean();
        ob_start();

        $debugTemplate = __DIR__ . DS . 'assets' . DS . 'debug.html';
        $htmlDebug = file_exists($debugTemplate) ? file_get_contents($debugTemplate) : '';

        if ($htmlDebug) {
            $htmlDebug = preg_replace(
                [
                    '/<!-- DEBUG CONTENT \(.+\) -->/mu',
                    '~<!--.*?-->~s',
                    '!/\*.*?\*/!s',
                    "/\n\s+/",
                    "/\n(\s*\n)+/",
                    "!\n//.*?\n!s",
                    "/\n\}(.+?)\n/",
                    "/\}\s+/",
                    "/,\n/",
                    "/>\n/",
                    "/\{\s*?\n/",
                    "/\}\n/",
                    "/;\n/",
                ],
                [
                    self::get(),
                    '',
                    '',
                    LF,
                    LF,
                    LF,
                    "}\\1\n",
                    '}',
                    ', ',
                    '>',
                    '{',
                    '} ',
                    ';',
                ],
                $htmlDebug
            );
        }

        echo preg_replace('/<\/body>/', $htmlDebug . '</body>', $content);
    }

    /**
     * Prints details about a variable.
     *
     * @param mixed $par
     *
     * @return string Retorna uma string HTML.
     */
    public static function printRc($par)
    {
        if (is_object($par)) {
            if (method_exists($par, '__toString')) {
                return str_replace(
                    '&lt;?php&nbsp;',
                    '',
                    str_replace(
                        '&nbsp;?&gt;',
                        '',
                        highlight_string('<?php ' . var_export($par->__toString(), true), true)
                    )
                );
            }

            return (PHP_SAPI === 'cli' || defined('STDIN'))
                ? print_r($par, true)
                : '<pre>' . print_r($par, true) . '</pre>';
        }

        return str_replace(
            '&lt;?php&nbsp;',
            '',
            str_replace(
                '&nbsp;?&gt;',
                '',
                highlight_string('<?php ' . print_r($par, true), true)
            )
        );
    }

    /**
     * Translates the backtrace array to internal backtrace array.
     *
     * @param array $backtrace
     * @param bool  $clean
     *
     * @return array
     */
    private static function translateBacktrace(array $backtrace, bool $clean = false): array
    {
        $translated = [];

        foreach ($backtrace as &$value) {
            $file = $value['file'] ?? null;
            $line = $value['line'] ?? null;

            if (!is_null($file)) {
                $lines = $clean
                    ? file($file)
                    : explode(
                        '<br />',
                        str_replace(
                            '<br /></span>',
                            '</span><br />',
                            highlight_file($file, true)
                        )
                    );
            }

            $translated[] = [
                'file' => $file,
                'line' => $line,
                'args' => $value['args'] ?? [],
                'content' => (is_null($file) || is_null($line))
                    ? 'unknown file'
                    : trim(preg_replace('/^(&nbsp;)+/', '', $lines[$line - 1])),
            ];

            // Releasing memory
            $lines = null;
            $value = null;
        }

        return $translated;
    }
}
