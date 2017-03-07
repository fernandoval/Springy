<?php
/** \file
 *  Springy.
 *
 *  \brief      URI handler class.
 *  \copyright  ₢ 2007-2016 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \author     Lucas Cardozo - lucas.cardozo@gmail.com
 *  \version    2.2.3.35
 *  \ingroup    framework
 */

namespace Springy;

use Springy\Utils\Strings_ANSI;
use Springy\Utils\Strings_UTF8;

/**
 *  \brief Classe para tratamento de URI.
 *
 *  Esta classe é estática e invocada automaticamente pelo framework.
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
     *  \brief Get the URI string.
     */
    private static function _fetch_uri_string()
    {
        // Verifica se há um único parâmetro na query string e esse parâmetro não é uma vafiável GET
        // DEPRECATED
        // if (is_array($_GET) && count($_GET) == 1 && (trim(key($_GET), '/') != '') && empty($_GET[key($_GET)])) {
            // return key($_GET);
        // }

        // There is the old SUPERVAR in $_GET, given by .htaccess?
        if (is_array($_GET) && !empty($_GET['SUPERVAR'])) {
            return $_GET['SUPERVAR'];
        }

        // The is REQUEST_URI?
        if (!empty($_SERVER['REQUEST_URI'])) {
            return explode('?', $_SERVER['REQUEST_URI'])[0];
        }

        // Não há QUERY_STRING? Então a variável ORIG_PATH_INFO existe?
        $path = (isset($_SERVER['ORIG_PATH_INFO'])) ? $_SERVER['ORIG_PATH_INFO'] : @getenv('ORIG_PATH_INFO');
        if (trim($path, '/') != '' && $path != '/'.pathinfo(__FILE__, PATHINFO_BASENAME)) {
            // remove caminho e informações do script, então temos uma boa URI
            return str_replace($_SERVER['SCRIPT_NAME'], '', $path);
        }

        return ''; // Uh oh! Huston, we have a problem!
    }

    /**
     *  \brief Define the name of the controller class.
     */
    private static function _set_class_controller($classname)
    {
        self::$class_controller = $classname;
    }

    /**
     *  \brief Parse the URI and initiate the internal variables.
     *
     *  Translate the URI in segments and query string variables. This method is used by the framework starter to determine the controller which is be called.
     *
     *  \return void.
     */
    public static function parseURI()
    {
        if (isset($_SERVER) && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'HEAD' && !isset($_SERVER['HTTP_HOST'])) {
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Cache-Control: private', false);
            die(md5(microtime()));
        }

        self::$uri_string = self::_fetch_uri_string();

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
            if (isset($_GET['SUPERVAR'])) {
                unset($_GET['SUPERVAR']);
            }
            self::redirect(self::buildURL(explode('/', trim(self::$uri_string, '/')), empty($_GET) ? [] : $_GET, false, 'dynamic', false), 301);
        }
        // Redireciona se for acesso à página inicial e a URI não terminar em / para mesma URL terminada com /
        elseif (self::$uri_string && substr(self::$uri_string, -1) != '/' && Configuration::get('uri', 'force_slash_on_index') && empty($Segments)) {
            if (isset($_GET['SUPERVAR'])) {
                unset($_GET['SUPERVAR']);
            }
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
            if ($key != 'SUPERVAR') {
                self::$get_params[$key] = $value;
            }
            unset($_GET[$key]);
        }

        $controller = null;

        // Procura a controller correta e corrige a página atual se necessário
        $path = Kernel::path(Kernel::PATH_CONTROLLER).(count(Kernel::controllerRoot()) ? DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, Kernel::controllerRoot()) : '');
        $segment = 0;
        while (self::getSegment($segment, false)) {
            $path .= DIRECTORY_SEPARATOR.self::getSegment($segment, false);
            $file = $path.'.page.php';
            if (file_exists($file)) {
                $controller = $file;
                self::setCurrentPage($segment);
                self::_set_class_controller(self::currentPage());
                break;
            } elseif (is_dir($path) && (!self::getSegment($segment + 1, false))) {
                $file = $path.DIRECTORY_SEPARATOR.'index.page.php';
                if (file_exists($file)) {
                    $controller = $file;
                    self::addSegment('index');
                    self::setCurrentPage($segment + 1);
                    self::_set_class_controller(self::currentPage());
                    break;
                }
            } elseif (is_dir($path)) {
                $file = $path.DIRECTORY_SEPARATOR.'index.page.php';
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
            self::_set_class_controller(self::currentPage());
        }

        // Varre as rotas alternativas de controladoras
        if (is_null($controller)) {
            $routes = Configuration::get('uri', 'routes');
            if (is_array($routes)) {
                foreach ($routes as $key => $data) {
                    if (preg_match('/^'.$key.'$/', $UriString, $matches)) {
                        if (isset($data['root_controller'])) {
                            Kernel::controllerRoot($data['root_controller']);
                        }
                        if (substr($data['controller'], 0, 1) == '$') {
                            $data['controller'] = $matches[(int) substr($data['controller'], 1)];
                        }
                        $controller = Kernel::path(Kernel::PATH_CONTROLLER).(count(Kernel::controllerRoot()) ? DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, Kernel::controllerRoot()) : '').DIRECTORY_SEPARATOR.$data['controller'].'.page.php';
                        self::_set_class_controller($data['controller']);
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
                    if (preg_match('/^'.$key.'$/', $UriString)) {
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
     *  \brief Validate the segments quantity for the current controller.
     */
    public static function validateURI()
    {
        if (!$pctlr = Configuration::get('uri', 'prevalidate_controller')) {
            return;
        }

        $ctrl = trim(str_replace(DIRECTORY_SEPARATOR, '/', (Kernel::controllerRoot() ? implode(DIRECTORY_SEPARATOR, Kernel::controllerRoot()) : '')).'/'.self::getControllerClass(), '/');

        if (isset($pctlr[$ctrl.'/'.self::getSegment(0)])) {
            $ctrl .= '/'.self::getSegment(0);
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
     *  \brief Return the name of the controller class.
     *
     *  \return A string with tha name of the controller class.
     */
    public static function getControllerClass()
    {
        return self::$class_controller;
    }

    /**
     *  \brief Return the current URI string.
     */
    public static function getURIString()
    {
        return self::$uri_string;
    }

    /**
     *  \brief Return the content of the segment which represent the current page.
     */
    public static function currentPage()
    {
        return self::getSegment(self::$segment_page, false);
    }

    /**
     *  \brief Return a string with the relative path to the current page.
     */
    public static function relativePathPage($consider_controller_root = false)
    {
        $path = (count(Kernel::controllerRoot()) && $consider_controller_root ? implode(DIRECTORY_SEPARATOR, Kernel::controllerRoot()) : '');
        for ($i = 0; $i < self::$segment_page; $i++) {
            $path .= (empty($path) ? '' : DIRECTORY_SEPARATOR).self::getSegment($i, false);
        }

        return $path;
    }

    /**
     *  \brief Return a string with the path URL the current page (without the protocol).
     */
    public static function currentPageURI()
    {
        return trim(str_replace(DIRECTORY_SEPARATOR, '/', self::relativePathPage()).'/'.self::currentPage(), '/');
    }

    /**
     *  \brief Define the segment of the current page.
     *
     *  \param[in] $segment_num integer with the number of the segment to fix as current page
     *  \return \c true if exists a $segment_num relative to the current page in the array of segments or \c false if does not exists.
     */
    public static function setCurrentPage($segment_num)
    {
        if (self::getSegment($segment_num, false)) {
            self::$segment_page = $segment_num;

            return true;
        }

        return false;
    }

    /**
     *  \brief Get any segment of the URI.
     *
     *  \param[in] $segment_num is an integer with the number of the segment desired.
     *  \param[in] $relative_to_page is a boolean value to determine if the desired segment is relative to the
     *      current page (default = true) or the begin (false) of the array of segments.
     *  \param[in] $consider_controller_root is a boolean value to determine if the number of segments of the
     *      root path of contollers must be decremented (true) or not (false = default).
     *  \return the value of the segment or \c false if it does not exists.
     */
    public static function getSegment($segment_num, $relative_to_page = true, $consider_controller_root = false)
    {
        if ($relative_to_page) {
            $segment_num += (1 + self::$segment_page);
        }
        if ($consider_controller_root) {
            $segment_num -= count(Kernel::controllerRoot());
        }
        if (array_key_exists($segment_num, self::$segments)) {
            return self::$segments[$segment_num];
        }

        return false;
    }

    /**
     *  \brief Get any ignored segment of the URI.
     *
     *  \param[in] $segment_num is an \c integer with the number of the segment desired.
     *  \return the value of the segment or \c false if it does not exists.
     */
    public static function getIgnoredSegment($segment_num)
    {
        if (array_key_exists($segment_num, self::$ignored_segments)) {
            return self::$ignored_segments[$segment_num];
        }

        return false;
    }

    /**
     *  \brief Return the array of segments.
     */
    public static function getAllSegments()
    {
        return self::$segments;
    }

    /**
     *  \brief Return the array of ignored segments.
     */
    public static function getAllIgnoredSegments()
    {
        return self::$ignored_segments;
    }

    /**
     *  \brief Add a segment to the end of segments array.
     *
     *  \param[in] $segment is an string with segment to be added to the end of the array of segments.
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
     *  \brief Insert a segment in any position of the segments array.
     *
     *  \param[in] (int) $position integer with the position where the segment must be inserted.
     *  \param[in] (string) $segment string with segment to be inserted.
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
     *  \brief Return the value of a query string variable.
     *
     *  \param[i] $var is the name of the query string variable desired.
     *  \return the value of the variable or \c false if it does not exists.
     */
    public static function _GET($var)
    {
        if (array_key_exists($var, self::$get_params)) {
            return self::$get_params[$var];
        }

        return false;
    }

    /**
     *  \brief get_param é um apelido para _GET
     *  \see _GET.
     */
    public static function getParam($var)
    {
        return self::_GET($var);
    }

    /**
     *  \brief Return the array of query string variables.
     */
    public static function getParams()
    {
        return self::$get_params;
    }

    /**
     *  \brief Return the request method string.
     */
    public static function requestMethod()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : ((PHP_SAPI === 'cli' || defined('STDIN')) ? 'CLI' : '');
    }

    /**
     *  \brief Remove a variable from the array of query string variables.
     *  \param[in] $var is the name of the query string variable to be deleted.
     */
    public static function removeParam($var)
    {
        unset(self::$get_params[$var]);
    }

    /**
     *  \brief Set value to a query string parameter.
     *
     *  \param[in] $var is the name of the query string variable
     *  \param[in] $value is the value to be assigned to the variable.
     */
    public static function setParam($var, $value)
    {
        self::$get_params[$var] = $value;
    }

    /**
     *  \brief Return the string of an URI with the received parameters.
     *
     *  \param[in] $segments is an \c array with the segments of the URL.
     *  \param[in] $query is an \c array with the query string variables.
     *  \param[in] $forceRewrite is a \c boolean value to define if URI will be writed in
     *      URL redirection form (user frendly - SEF) forced or the value of configuration will be used to it.
     *  \param[in] $include_ignores_segments is a \c boolean value to define if URI will receive the ignored segments as prefix (default = \c true).
     *  \return an URI.
     */
    public static function buildURL($segments = [], $query = [], $forceRewrite = false, $host = 'dynamic', $include_ignores_segments = true)
    {
        if ($include_ignores_segments) {
            $segments = array_merge(self::$ignored_segments, is_array($segments) ? $segments : [$segments]);
        }

        $url = str_replace('//', '/', Configuration::get('uri', 'system_root').'/');

        // Se rewrite de URL está desligado e não está sendo forçado, acrescenta ? à URL
        if (Configuration::get('system', 'rewrite_url') === false && $forceRewrite === false) {
            $url .= '?';
        }

        // Monta a URI
        $uri = '';
        for ($i = 0; $i < count($segments); $i++) {
            if ($segments[$i] != 'index' && $segments[$i] != '') {
                $uri .= (empty($uri) ? '' : '/').self::makeSlug($segments[$i], '-', '\.,|~\#', false);
            }
        }
        $url .= $uri;

        /*if (Configuration::get('system', 'ext_file_url')) {
            $url .= Configuration::get('system', 'ext_file_url');
        }*/

        // Monta os parâmetros a serem passados por GET
        self::encode_param($query, '', $param);

        return self::_host($host).$url.$param;
    }

    /**
     *  \brief Return the current host with protocol.
     */
    public static function http_host()
    {
        return trim(preg_replace('/([^:]+)(:\\d+)?/', '$1'.((isset($GLOBALS['SYSTEM']['CONSIDER_PORT_NUMBER']) && $GLOBALS['SYSTEM']['CONSIDER_PORT_NUMBER']) ? '$2' : ''), isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''), ' ..@');
    }

    /**
     *  \brief Retorna o host com protocolo.
     *
     *  \param[in] $host String contendo o host com ou sem o protocolo, ou a entrada de configuração do host
     *  \return Retorna a string contendo o protocolo e o host
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

        return (isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$host;
    }

    /**
     *  \brief Codifica os parâmetros GET de uma URI.
     *
     *  \param[in] $query Array contendo os parâmetros chave => valor
     *  \param[in] $key Nome da chave para quando query possuir apenas os valores
     *  \param[out] $param variável de retorno da query string
     *  \return Void
     */
    private static function encode_param($query, $key, &$param)
    {
        foreach ($query as $var => $value) {
            if (is_array($value)) {
                self::encode_param($value, $var.'['.key($value).']', $param);
            } else {
                $param .= (empty($param) ? '?' : '&').($key ? $key : $var).'='.urlencode($value);
            }
        }
    }

    /**
     *  \brief Set a redirect status header and finish the application.
     *
     *  This method sends the status header with a URI redirection to the user browser and finish the application execution.
     *
     *  \param[in] $url is a string with the URI.
     *  \param[in] $header is an integer value with the redirection code (default = 302).\n
     *      (302 = permanente, 301 = temporário).
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

        header('HTTP/1.1 '.$header.(isset($redirs[$header]) ? $redirs[$header] : ''), true);
        header('Status: '.$header, true);
        header('Location: '.$url, true, $header);
        exit;
    }

    /**
     *  \brief Generate a slug, removing the accented and special characters from a string and convert spaces into minus symbol.
     *
     *  \param[in] $txt is a \c string with the text to be converted to slug format.
     *  \paran[in] $space is a \c string with the character used as word separator. (default = '-')
     *  \param[in] $accept is a \c string with other characters to be added to regular expression of accpted characters is slug.
     *  \param[in] $lowercase is a \c boolean value that determine if the slug will be returned as lowercase string or as is.
     *  \return the slug string.
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
        $txt = mb_ereg_replace('[^a-zA-Z0-9 _\-'.$accept.']', '', $txt);
        $txt = mb_ereg_replace('[ ]+', $space, $txt);

        return $txt;
    }

    /**
     *  \brief Return true if is an XML HTTL request (AJAX).
     *
     *  Verifica se a requisição recebida contém HTTP_X_REQUESTED_WITH no cabeçalho do pacote.
     *  Test to see if a request contains the HTTP_X_REQUESTED_WITH header.
     *
     *  \return \c true if the request is an AJAX call.
     */
    public static function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
