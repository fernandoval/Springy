<?php

/**
 * URI handler class.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Lucas Cardozo <lucas.cardozo@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   2.2.12
 */

namespace Springy;

use Springy\Utils\Strings_ANSI;
use Springy\Utils\Strings_UTF8;

/**
 * Classe para tratamento de URI.
 *
 * Esta classe é estática e invocada automaticamente pelo framework.
 */
class URI
{
    /// String da URI
    private static $uri_string = '';
    /// Array dos segmentos da URI
    private static $segments = [];
    /// Array dos segmentos ignorados
    private static $ignored_segments = [];
    /// Array da relação dos parâmetros recebidos por GET
    private static $get_params = [];
    /// Índice do segmento que determina a página atual
    private static $segment_page = 0;
    /// Nome da classe da controller
    private static $class_controller = null;

    /**
     * Get the URI string.
     *
     * @return string
     */
    private static function _fetchURItring()
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
     * Define the name of the controller class.
     *
     * @return void
     */
    private static function _setClassController($classname)
    {
        self::$class_controller = $classname;
    }

    /**
     * Parses the URI and initiate the internal variables.
     *
     * Translate the URI in segments and query string variables.
     * This method is used by the framework starter to determine the controller which is be called.
     *
     * @return string|null.
     */
    public static function parseURI()
    {
        if (isset($_SERVER) && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'HEAD' && !isset($_SERVER['HTTP_HOST'])) {
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Cache-Control: private', false);
            exit(md5(microtime()));
        }

        self::$uri_string = self::_fetchURItring();

        $UriString = trim(self::$uri_string, '/');

        // Verifica se altera o diretório de controladoras para o HOST
        if ($url = (isset($_SERVER) && isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '') {
            foreach (Configuration::get('uri', 'host_controller_path') as $host => $root) {
                if ($url == $host) {
                    Kernel::controllerRoot($root);
                    break;
                }
            }
        }

        // Processa a URI e separa os segmentos
        $Segments = [];
        $SegNum = 0;
        foreach (explode('/', preg_replace('|/*(.+?)/*$|', '\\1', $UriString)) as $val) {
            $val = trim($val);

            if ($val != '') {
                if ($SegNum < Configuration::get('uri', 'ignored_segments')) {
                    self::$ignored_segments[] = $val;
                } else {
                    $Segments[] = $val;
                }
            }
            $SegNum++;
        }

        // Redireciona URIs terminadas em / para evitar conteúdo duplicado de SEO?
        if (Configuration::get('uri', 'redirect_last_slash') && substr(self::$uri_string, -1) == '/' && !(Configuration::get('uri', 'force_slash_on_index') && empty($Segments))) {
            self::redirect(self::buildURL(explode('/', trim(self::$uri_string, '/')), empty($_GET) ? [] : $_GET, false, 'dynamic', false), 301);
        }
        // Redireciona se for acesso à página inicial e a URI não terminar em / para mesma URL terminada com /
        elseif (self::$uri_string && substr(self::$uri_string, -1) != '/' && Configuration::get('uri', 'force_slash_on_index') && empty($Segments)) {
            self::redirect(self::buildURL(array_merge(explode('/', trim(self::$uri_string, '/')), ['/']), empty($_GET) ? [] : $_GET, false, 'dynamic', false), 301);
        }

        // Se nenhum segmento foi encontrado, define o segmento 'index'
        if (empty($Segments)) {
            $Segments[] = 'index';
        }

        // Define o primeiro segmento da URI como sendo a página solicitada
        //self::$segment_page = (trim($Segments[0]) ? $Segments[0] : 'index');
        //array_shift($Segments);

        // Guarda os demais segmentos da URI no atributo interno
        foreach ($Segments as $segment) {
            if (trim($segment) != '') {
                self::$segments[] = $segment;
            }
        }

        // Guarda os parâmetros passados por GET no atributo interno
        foreach ($_GET as $key => $value) {
            self::$get_params[$key] = $value;
            unset($_GET[$key]);
        }

        $controller = null;

        // Procura a controller correta e corrige a página atual se necessário
        $path = Kernel::path(Kernel::PATH_CONTROLLER) . (count(Kernel::controllerRoot()) ? DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, Kernel::controllerRoot()) : '');
        $segment = 0;
        while (self::getSegment($segment, false)) {
            $path .= DIRECTORY_SEPARATOR . self::getSegment($segment, false);
            $file = $path . '.page.php';
            if (file_exists($file)) {
                $controller = $file;
                self::setCurrentPage($segment);
                self::_setClassController(self::currentPage());
                break;
            } elseif (is_dir($path) && (!self::getSegment($segment + 1, false))) {
                $file = $path . DIRECTORY_SEPARATOR . 'index.page.php';
                if (file_exists($file)) {
                    $controller = $file;
                    self::addSegment('index');
                    self::setCurrentPage($segment + 1);
                    self::_setClassController(self::currentPage());
                    break;
                }
            } elseif (is_dir($path)) {
                $file = $path . DIRECTORY_SEPARATOR . 'index.page.php';
                if (file_exists($file)) {
                    $possible_controller = $file;
                    $possible_segment_name = 'index';
                    $possible_segment_num = $segment + 1;
                }
                $segment++;
            } else {
                break;
            }
        }

        // Verifica se nenhuma controladora foi localizada, mas há uma elegível
        if (is_null($controller) && isset($possible_controller)) {
            $controller = $possible_controller;
            self::insertSegment($possible_segment_num, $possible_segment_name);
            self::setCurrentPage($possible_segment_num);
            self::_setClassController(self::currentPage());
        }

        // Varre as rotas alternativas de controladoras
        if (is_null($controller)) {
            $routes = Configuration::get('uri', 'routes');
            if (is_array($routes)) {
                foreach ($routes as $key => $data) {
                    if (preg_match('/^' . $key . '$/', $UriString, $matches)) {
                        if (isset($data['root_controller'])) {
                            Kernel::controllerRoot($data['root_controller']);
                        }
                        if (substr($data['controller'], 0, 1) == '$') {
                            $data['controller'] = $matches[(int) substr($data['controller'], 1)];
                        }
                        $controller = Kernel::path(Kernel::PATH_CONTROLLER) . (count(Kernel::controllerRoot()) ? DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, Kernel::controllerRoot()) : '') . DIRECTORY_SEPARATOR . $data['controller'] . '.page.php';
                        self::_setClassController($data['controller']);
                        self::setCurrentPage($data['segment']);
                        break;
                    }
                }
            }
            unset($routes);
        }

        // define o namespace do controller em relação a raiz da pasta de controlers
        Kernel::controllerNamespace($controller);

        // Varre os redirecionamentos
        if (is_null($controller)) {
            $redirects = Configuration::get('uri', 'redirects');
            if (is_array($redirects)) {
                foreach ($redirects as $key => $data) {
                    if (preg_match('/^' . $key . '$/', $UriString)) {
                        foreach ($data['segments'] as $segment => $value) {
                            if (substr($value, 0, 1) == '$') {
                                $data['segments'][$segment] = isset(self::$segments[(int) substr($value, 1)]) ? self::$segments[(int) substr($value, 1)] : '';
                            }
                        }
                        self::redirect(self::buildURL($data['segments'], $data['get'], $data['force_rewrite'], $data['host']), $data['type']);
                        break;
                    }
                }
            }
            unset($redirects);
        }

        return $controller;
    }

    /**
     * Validates the segments quantity for the current controller.
     *
     * @return void
     */
    public static function validateURI()
    {
        if (!$pctlr = Configuration::get('uri', 'prevalidate_controller')) {
            return;
        }

        $ctrl = trim(str_replace(DIRECTORY_SEPARATOR, '/', (Kernel::controllerRoot() ? implode(DIRECTORY_SEPARATOR, Kernel::controllerRoot()) : '')) . '/' . self::getControllerClass(), '/');

        if (isset($pctlr[$ctrl . '/' . self::getSegment(0)])) {
            $ctrl .= '/' . self::getSegment(0);
        }

        if (!isset($pctlr[$ctrl]) && !isset($pctlr[$ctrl]['command'])) {
            return;
        }

        $action = 200;
        if (isset($pctlr[$ctrl]['segments']) && count(self::$segments) - self::$segment_page - 1 > $pctlr[$ctrl]['segments']) {
            $action = $pctlr[$ctrl]['command'];
        }
        if (isset($pctlr[$ctrl]['validate'])) {
            foreach ($pctlr[$ctrl]['validate'] as $idx => $expression) {
                if (!preg_match($expression, self::getSegment($idx))) {
                    $action = $pctlr[$ctrl]['command'];
                }
            }
        }

        switch ($action) {
            case 301:
            case 302:
                while (count(self::$segments) - self::$segment_page - 1 > $pctlr[$ctrl]['segments']) {
                    array_pop(self::$segments);
                }
                self::redirect(self::buildURL(self::$segments, empty($_GET) ? [] : $_GET), $action);
            case 404:
            case 500:
            case 503:
                new Errors($action);
        }
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
        $path = (count(Kernel::controllerRoot()) && $consider_controller_root ? implode(DIRECTORY_SEPARATOR, Kernel::controllerRoot()) : '');
        for ($i = 0; $i < self::$segment_page; $i++) {
            $path .= (empty($path) ? '' : DIRECTORY_SEPARATOR) . self::getSegment($i, false);
        }

        return $path;
    }

    /**
     * Returns a string with the path URL the current page (without the protocol).
     *
     * @return string
     */
    public static function currentPageURI()
    {
        return trim(str_replace(DIRECTORY_SEPARATOR, '/', self::relativePathPage()) . '/' . self::currentPage(), '/');
    }

    /**
     * Defines the segment of the current page.
     *
     * @param int $segment integer with the number of the segment to fix as current page.
     *
     * @return bool true if exists a $segment relative to the current page in the array of segments or false if does not exists.
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
     * @return mixed the value of the segment or false if it does not exists.
     */
    public static function getSegment($segment, $rel2Page = true, $decRoot = false)
    {
        if ($rel2Page) {
            $segment += (1 + self::$segment_page);
        }
        if ($decRoot) {
            $segment -= count(Kernel::controllerRoot());
        }
        if (array_key_exists($segment, self::$segments)) {
            return self::$segments[$segment];
        }

        return false;
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
     * Is an alias for URI::getParam().
     *
     * @param string $var is the name of the query string variable desired.
     *
     * @return mixed the value of the variable or false if it does not exists.
     *
     * @deprecated 4.4.0
     */
    public static function _GET($var)
    {
        return self::getParam($var);
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
     * @return voit
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
    public static function buildURL($segments = [], $query = [], $forceRewrite = false, $host = 'dynamic', $addIgnoredSgms = true)
    {
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

        /*if (Configuration::get('system', 'ext_file_url')) {
            $url .= Configuration::get('system', 'ext_file_url');
        }*/

        // Monta os parâmetros a serem passados por GET
        self::encodeParam($query, '', $param);

        return self::_host($host) . $url . $param;
    }

    /**
     * Returns the current host with protocol.
     *
     * @return string
     */
    public static function httpHost()
    {
        return trim(
            preg_replace(
                '/([^:]+)(:\\d+)?/',
                '$1' . ((sysconf('CONSIDER_PORT_NUMBER') !== null && sysconf('CONSIDER_PORT_NUMBER')) ? '$2' : ''),
                $_SERVER['HTTP_HOST'] ?? ''
            ),
            ' ..@'
        );
    }

    /**
     * Returns the host protocol.
     *
     * @param string $host the with or without protocol prefix, or the configuration entry for host.
     *
     * @return string
     */
    private static function _host($host = 'dynamic')
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
     * This method sends the status header with a URI redirection to the user browser and finish the application execution.
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
     * Generates a slug, removing the accented and special characters from a string and convert spaces into minus symbol.
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
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
