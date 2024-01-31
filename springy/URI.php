<?php

/**
 * URI handler class.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Lucas Cardozo <lucas.cardozo@gmail.com>
 *
 * @version   3.0.0
 */

namespace Springy;

use Springy\Utils\Strings_ANSI;
use Springy\Utils\Strings_UTF8;

class URI
{
    /** @var string HTTP host */
    private static $httpHost = '';
    /** @var string The URI string */
    private static $uri_string = '';
    /** @var array The path segments */
    private static $segments = [];
    /** @var array Ignored segments */
    private static $ignored_segments = [];
    /** @var array Query string parameters array */
    private static $get_params = [];
    /** @var int Current page segment index */
    private static $segment_page = 0;
    /** @var string|null Class name of the controller */
    private static $class_controller = null;

    /**
     * Checks whether request is a head method.
     *
     * @return void
     */
    private static function checkHeadMethod(): void
    {
        if (
            isset($_SERVER)
            && isset($_SERVER['REQUEST_METHOD'])
            && $_SERVER['REQUEST_METHOD'] == 'HEAD'
            && !isset($_SERVER['HTTP_HOST'])
        ) {
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Cache-Control: private', false);
            exit(md5(microtime()));
        }
    }

    /**
     * Checks whether to redirect requests ending with slash.
     *
     * @param array $segments
     *
     * @return void
     */
    private static function checkRedirEndingBar(array $segments): void
    {
        if (
            Configuration::get('uri', 'redirect_last_slash')
            && substr(self::$uri_string, -1) == '/'
            && !(Configuration::get('uri', 'force_slash_on_index')
            && empty($segments))
        ) {
            dd(__LINE__);
            // Redirects URIs ending with slash to avoid SEO problem.
            self::redirect(
                self::buildURL(
                    explode('/', trim(self::$uri_string, '/')),
                    $_GET ?? [],
                    false,
                    'dynamic',
                    false
                ),
                301
            );
        } elseif (
            self::$uri_string
            && substr(self::$uri_string, -1) != '/'
            && Configuration::get('uri', 'force_slash_on_index')
            && empty($segments)
        ) {
            dd(__LINE__);
            // Removes ending slash and redirects
            self::redirect(
                self::buildURL(
                    array_merge(explode('/', trim(self::$uri_string, '/')), ['/']),
                    $_GET ?? [],
                    false,
                    'dynamic',
                    false
                ),
                301
            );
        }
    }

    /**
     * Checks URI redirect option for not found controller.
     *
     * @param string|null $controller
     * @param string      $uriString
     *
     * @return void
     */
    private static function checkRedirect(?string $controller, string $uriString): void
    {
        if (!is_null($controller)) {
            return;
        }

        $redirects = Configuration::get('uri', 'redirects');

        if (is_array($redirects)) {
            foreach ($redirects as $key => $data) {
                if (preg_match('/^' . $key . '$/', $uriString)) {
                    foreach ($data['segments'] as $segment => $value) {
                        if (substr($value, 0, 1) == '$') {
                            $data['segments'][$segment] = self::$segments[(int) substr($value, 1)] ?? '';
                        }
                    }

                    self::redirect(
                        self::buildURL($data['segments'], $data['get'], $data['force_rewrite'], $data['host']),
                        $data['type']
                    );

                    break;
                }
            }
        }
    }

    /**
     * Gets the URI string.
     *
     * @return string
     */
    private static function fetchURI(): string
    {
        // The is REQUEST_URI?
        if (!empty($_SERVER['REQUEST_URI'])) {
            return explode('?', $_SERVER['REQUEST_URI'])[0];
        }

        // Não há QUERY_STRING? Então a variável ORIG_PATH_INFO existe?
        $path = (isset($_SERVER['ORIG_PATH_INFO'])) ? $_SERVER['ORIG_PATH_INFO'] : @getenv('ORIG_PATH_INFO');
        if (trim($path, '/') != '' && $path != '/' . pathinfo(__FILE__, PATHINFO_BASENAME)) {
            // remove caminho e informações do script, então temos uma boa URI
            return str_replace($_SERVER['SCRIPT_NAME'], '', $path);
        }

        return ''; // Uh oh! Huston, we have a problem!
    }

    /**
     * Converts the URI string into an array.
     *
     * @param string $uriString
     *
     * @return array
     */
    private static function getSegmentsFromURI(string $uriString): array
    {
        $segments = [];
        $segNum = 0;

        foreach (explode('/', preg_replace('|/*(.+?)/*$|', '\\1', $uriString)) as $val) {
            $val = trim($val);

            if ($val != '') {
                if ($segNum < Configuration::get('uri', 'ignored_segments')) {
                    self::$ignored_segments[] = $val;
                } else {
                    $segments[] = $val;
                }
            }

            $segNum++;
        }

        return array_filter(
            $segments,
            function ($seg) {
                return $seg !== '';
            }
        );
    }

    /**
     * Parses the $_SERVER['HTTP_HOST] variable.
     *
     * @return void
     */
    private static function parseHost(): void
    {
        if (php_sapi_name() === 'cli') {
            self::$httpHost = 'cmd.shell';

            return;
        }

        self::$httpHost = preg_replace(
            '/([^:]+)(:\\d+)?/',
            '$1',
            $_SERVER['HTTP_HOST'] ?? ''
        ) . (
            ($_SERVER['SERVER_PORT'] ?? 80) != 80
            ? ':' . $_SERVER['SERVER_PORT']
            : ''
        );
    }

    /**
     * Defines the name of the controller class.
     *
     * @return void
     */
    public static function setClassController($classname)
    {
        self::$class_controller = $classname;
    }

    /**
     * Returns the current host with port number but without protocol.
     *
     * @return string
     */
    public static function getHost(): string
    {
        return self::$httpHost;
    }

    /**
     * Parses the URI and initiate the internal variables.
     *
     * Translate the URI in segments and query string variables.
     * This method is used by the framework starter to determine the controller which is be called.
     *
     * @return void.
     */
    public static function parseURI(): void
    {
        self::checkHeadMethod();
        self::parseHost();
        self::$uri_string = self::fetchURI();
        $uriString = trim(self::$uri_string, '/');
        $segments = self::getSegmentsFromURI($uriString);
        self::checkRedirEndingBar($segments);

        // Adds 'index' if there is no segments
        if (count($segments) === 0) {
            $segments[] = '';
        }

        self::$segments = $segments;
        self::$get_params = $_GET;
        $_GET = [];
    }

    /**
     * Returns the name of the controller class.
     *
     * @return string
     */
    public static function getControllerClass()
    {
        return self::$class_controller;
    }

    /**
     * Returns the index of the current page controller.
     *
     * @return int
     */
    public static function getSegmentPage(): int
    {
        return self::$segment_page;
    }

    /**
     * Returns the current URI string.
     *
     * @return string
     */
    public static function getURIString()
    {
        return self::$uri_string;
    }

    /**
     * Returns the content of the segment which represent the current page.
     *
     * @return string
     */
    public static function currentPage()
    {
        return self::getSegment(self::$segment_page, false);
    }

    /**
     * Returns a string with the relative path to the current page.
     *
     * @return string
     */
    public static function relativePathPage($consider_controller_root = false)
    {
        $path = count(Kernel::controllerRoot()) && $consider_controller_root
            ? implode(DS, Kernel::controllerRoot())
            : '';
        $segs = array_slice(self::$segments, 0, self::$segment_page);

        if (!empty($path)) {
            array_unshift($segs, $path);
        }

        return implode(DS, $segs);
    }

    /**
     * Returns a string with the path URL the current page (without the protocol).
     *
     * @return string
     */
    public static function currentPageURI()
    {
        return trim(
            str_replace(
                DS,
                '/',
                self::relativePathPage()
            ) . '/' . (self::$segments[self::$segment_page] ?? ''),
            '/'
        );
    }

    /**
     * Defines the segment of the current page.
     *
     * @param int $segment integer with the number of the segment to fix as current page.
     *
     * @return bool true if exists a $segment relative to the current page in
     *              the array of segments or false if does not exists.
     */
    public static function setCurrentPage($segment)
    {
        if (self::getSegment($segment, false)) {
            self::$segment_page = $segment;

            return true;
        }

        return false;
    }

    /**
     * Gets any segment of the URI.
     *
     * @param int  $segment  is an integer with the number of the segment desired.
     * @param bool $rel2Page is a boolean value to determine if the desired segment is relative to the
     *                       current page (default = true) or the begin (false) of the array of segments.
     * @param bool $decRoot  is a boolean value to determine if the number of segments of the
     *                       root path of contollers must be decremented (true) or not (false = default).
     *
     * @return string|bool the value of the segment or false if it does not exists.
     */
    public static function getSegment(int $segment, bool $rel2Page = true, bool $decRoot = false)
    {
        $realSegment = $segment
            + ($rel2Page ? 1 + self::$segment_page : 0)
            - ($decRoot ? count(Kernel::controllerRoot()) : 0);
        $value = self::$segments[$realSegment] ?? false;

        return $value === '' ? 'index' : $value;
    }

    /**
     * Gets any ignored segment of the URI.
     *
     * @param int $segment
     *
     * @return mixed the value of the segment or false if it does not exists.
     */
    public static function getIgnoredSegment($segment)
    {
        if (array_key_exists($segment, self::$ignored_segments)) {
            return self::$ignored_segments[$segment];
        }

        return false;
    }

    /**
     * Returns the array of segments.
     *
     * @return array
     */
    public static function getAllSegments()
    {
        return self::$segments;
    }

    /**
     * Returns the array of ignored segments.
     *
     * @return array
     */
    public static function getAllIgnoredSegments()
    {
        return self::$ignored_segments;
    }

    /**
     * Adds a segment to the end of segments array.
     *
     * @param string $segment is an string with segment to be added to the end of the array of segments.
     */
    public static function addSegment($segment)
    {
        if (trim($segment) != '') {
            self::$segments[] = $segment;

            return true;
        }

        return false;
    }

    /**
     * Inserts a segment in any position of the segments array.
     *
     * @param int    $position integer with the position where the segment must be inserted.
     * @param string $segment  string with segment to be inserted.
     *
     * @return bool
     */
    public static function insertSegment($position, $segment)
    {
        if (trim($segment) != '') {
            array_splice(self::$segments, $position, 0, [$segment]);

            return true;
        }

        return false;
    }

    /**
     * Returns the value of a query string variable.
     *
     * @param string $var     is the name of the query string variable desired.
     * @param bool   $numeric true if parameter needs to be integer.
     *
     * @return mixed the value of the variable or false if it does not exists.
     */
    public static function getParam($var, $numeric = false)
    {
        if (array_key_exists($var, self::$get_params)) {
            if (!$numeric || ($numeric && filter_var(self::$get_params[$var], FILTER_VALIDATE_INT))) {
                return self::$get_params[$var];
            }
        }

        return false;
    }

    /**
     * Returns the array of query string variables.
     *
     * @return array
     */
    public static function getParams()
    {
        return self::$get_params;
    }

    /**
     * Returns the request method string.
     *
     * @return string
     */
    public static function requestMethod()
    {
        return $_SERVER['REQUEST_METHOD'] ?? ((PHP_SAPI === 'cli' || defined('STDIN')) ? 'CLI' : '');
    }

    /**
     * Removes a variable from the array of query string variables.
     *
     * @param string $var the name of the query string variable to be deleted.
     *
     * @return void
     */
    public static function removeParam($var)
    {
        unset(self::$get_params[$var]);
    }

    /**
     * Sets value to a query string parameter.
     *
     * @param string $var   the name of the query string variable.
     * @param mixed  $value the value to be assigned to the variable.
     *
     * @return void
     */
    public static function setParam($var, $value)
    {
        self::$get_params[$var] = $value;
    }

    /**
     * Returns the string of an URI with the received parameters.
     *
     * @param array  $segments       the segments of the URL.
     * @param array  $query          the query string variables.
     * @param bool   $forceRewrite   define if URI will be writed in
     *                               URL redirection form (user frendly - SEF)
     *                               forced or the value of configuration will be used to it.
     * @param string $host           the configuration host name.
     * @param bool   $addIgnoredSgms define if URI will receive the ignored segments as prefix (default = true).
     *
     * @return string
     */
    public static function buildURL(
        $segments = [],
        $query = [],
        $forceRewrite = false,
        $host = 'dynamic',
        $addIgnoredSgms = true
    ): string {
        if ($addIgnoredSgms) {
            $segments = array_merge(self::$ignored_segments, is_array($segments) ? $segments : [$segments]);
        }

        $url = str_replace('//', '/', Configuration::get('uri', 'system_root') . '/');

        // Se rewrite de URL está desligado e não está sendo forçado, acrescenta ? à URL
        if (Configuration::get('system', 'rewrite_url') === false && $forceRewrite === false) {
            $url .= '?';
        }

        // Monta a URI
        $uri = '';
        for ($i = 0; $i < count($segments); $i++) {
            if ($segments[$i] != 'index' && $segments[$i] != '') {
                $uri .= (empty($uri) ? '' : '/') . self::makeSlug($segments[$i], '-', '\.,|~\#', false);
            }
        }
        $url .= $uri;

        // Monta os parâmetros a serem passados por GET
        self::encodeParam($query, '', $param);

        return self::host($host) . $url . $param;
    }

    /**
     * Returns the host protocol.
     *
     * @param string $host the with or without protocol prefix, or the configuration entry for host.
     *
     * @return string
     */
    private static function host($host = 'dynamic')
    {
        if (preg_match('|^(.+):\/\/(.+)|i', $host)) {
            return $host;
        } elseif ($host = Configuration::get('uri', $host)) {
            if (preg_match('|^(.+):\/\/(.+)|i', $host)) {
                return $host;
            }
        }

        return (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $host;
    }

    /**
     * Encondes the query string parameters.
     *
     * @param array  $query Array contendo os parâmetros chave => valor
     * @param string $key   Nome da chave para quando query possuir apenas os valores
     * @param string $param variável de retorno da query string
     *
     * @return void
     */
    private static function encodeParam($query, $key, &$param)
    {
        foreach ($query as $var => $value) {
            if (is_array($value)) {
                self::encodeParam($value, $var . '[' . key($value) . ']', $param);
            } else {
                $param .= (empty($param) ? '?' : '&') . ($key ? $key : $var) . '=' . urlencode($value);
            }
        }
    }

    /**
     * Sets a redirect status header and finish the application.
     *
     * This method sends the status header with a URI redirection to the user
     * browser and finish the application execution.
     *
     * @param string $url    the URI.
     * @param int    $header the redirection code (default = 302).
     *
     * @return void
     */
    public static function redirect($url, $header = 302)
    {
        $redirs = [
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            307 => 'Temporary Redirect',
        ];

        if (ob_get_level() > 0) {
            ob_clean();
        }

        header('HTTP/1.1 ' . $header . (isset($redirs[$header]) ? $redirs[$header] : ''), true);
        header('Status: ' . $header, true);
        header('Location: ' . $url, true, $header);

        if (Kernel::isCGIMode()) {
            $lineFeed = "\n";
            echo 'Status: ' . $header . $lineFeed;
            echo 'HTTP/1.1 ' . $header . ($redirs[$header] ?? '') . $lineFeed;
            echo 'Location: ' . $url . $lineFeed . $lineFeed;
        }

        exit;
    }

    /**
     * Generates a slug, removing the accented and special characters from a
     * string and convert spaces into minus symbol.
     *
     * @param string $txt       the text to be converted to slug format.
     * @param string $space     the character used as word separator (default = '-').
     * @param string $accept    other characters to be added to regular expression of accpted characters is slug.
     * @param bool   $lowercase determine if the slug will be returned as lowercase string or as is.
     *
     * @return string
     */
    public static function makeSlug($txt, $space = '-', $accept = '', $lowercase = true)
    {
        if (mb_check_encoding($txt, 'UTF-8')) {
            $txt = Strings_UTF8::removeAccentedChars($txt);
        } else {
            $txt = Strings_ANSI::removeAccentedChars($txt);
        }

        if ($lowercase) {
            $txt = mb_strtolower(trim($txt));
        } else {
            $txt = trim($txt);
        }

        $txt = mb_ereg_replace('[  ]+', ' ', $txt);
        //$txt = mb_ereg_replace('[--]+', '-', $txt);
        //$txt = mb_ereg_replace('[__]+', '_', $txt);
        $txt = mb_ereg_replace('[^a-zA-Z0-9 _\-' . $accept . ']', '', $txt);
        $txt = mb_ereg_replace('[ ]+', $space, $txt);
        $txt = preg_replace('/' . $space . $space . '+/', $space, $txt);

        return $txt;
    }

    /**
     * Returns true if is an XML HTTL request (AJAX).
     *
     * Verifica se a requisição recebida contém HTTP_X_REQUESTED_WITH no cabeçalho do pacote.
     * Test to see if a request contains the HTTP_X_REQUESTED_WITH header.
     *
     * @return bool true if the request is an AJAX call.
     */
    public static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
