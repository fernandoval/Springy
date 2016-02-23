<?php
/** \file
 *  FVAL PHP Framework for Web Applications.
 *
 *  \copyright  Copyright (c) 2007-2016 FVAL Consultoria e Informática Ltda.\n
 *  \copyright  Copyright (c) 2007-2016 Fernando Val\n
 *  \copyright  Copyright (c) 2009-2013 Lucas Cardozo
 *
 *  \brief      Classe para tratamento de URI
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    2.1.30
 *  \author     Fernando Val  - fernando.val@gmail.com
 *  \author     Lucas Cardozo - lucas.cardozo@gmail.com
 *  \ingroup    framework
 */
namespace FW;

use FW\Utils\Strings_ANSI;
use FW\Utils\Strings_UTF8;

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
     *  \brief Lê a URLs (em modo re-write) e inicializa a variável $uri_string interna.
     *
     *  \return \c true se houve sucesso no processo e \c false em caso contrário
     */
    private static function _fetch_uri_string()
    {
        // Verifica se há um único parâmetro na query string e esse parâmetro não é uma vafiável GET
        if (is_array($_GET) && count($_GET) == 1 && (trim(key($_GET), '/') != '') && empty($_GET[key($_GET)])) {
            self::$uri_string = key($_GET);

            return true;
        }

        // A variável SUPERVAR foi setada na query string pelo .htacess ou enviada por GET?
        if (is_array($_GET) && !empty($_GET['SUPERVAR'])) {
            self::$uri_string = $_GET['SUPERVAR'];

            return true;
        }

        // A variável PATH_INFO existe?
        $path = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
        if (trim($path, '/') != '' && $path != '/'.pathinfo(__FILE__, PATHINFO_BASENAME)) {
            self::$uri_string = trim($path, '&');

            return true;
        }

        // Não há PATH_INFO? A entrada QUERY_STRING existe?
        /*$path = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
        if (trim($path, '/') != '') {
            self::$uri_string = $path;
            return true;
        }*/

        // Não há QUERY_STRING? Então a variável ORIG_PATH_INFO existe?
        $path = (isset($_SERVER['ORIG_PATH_INFO'])) ? $_SERVER['ORIG_PATH_INFO'] : @getenv('ORIG_PATH_INFO');
        if (trim($path, '/') != '' && $path != '/'.pathinfo(__FILE__, PATHINFO_BASENAME)) {
            // remove caminho e informações do script, então temos uma boa URI
            self::$uri_string = str_replace($_SERVER['SCRIPT_NAME'], '', $path);

            return true;
        }

        // Se esgotaram todas as opções...
        self::$uri_string = '';

        return false;
    }

    /**
     *  \brief Define o nome da classe da controller.
     */
    private static function _set_class_controller($classname)
    {
        self::$class_controller = $classname;
    }

    /**
     *  \brief Lê a URLs (em modo re-write) e transforma em variáveis $_GET.
     *
     *  \note Este método não retorna valor
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

        self::_fetch_uri_string();

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
     *  \brief Valida a quantidade de segmentos da URI conforme a controladora.
     */
    public static function validateURI()
    {
        $ctrl = trim(str_replace(DIRECTORY_SEPARATOR, '/', (Kernel::controllerRoot() ? implode(DIRECTORY_SEPARATOR, Kernel::controllerRoot()) : '')).'/'.self::getControllerClass(), '/');

        if ($pctlr = Configuration::get('uri', 'prevalidate_controller')) {
            if (isset($pctlr[$ctrl.'/'.self::getSegment(0)])) {
                $ctrl .= '/'.self::getSegment(0);
            }

            if (isset($pctlr[$ctrl]) && isset($pctlr[$ctrl]['command'])) {
                $action = 200;
                if (isset($pctlr[$ctrl]['segments'])) {
                    if (count(self::$segments) - self::$segment_page - 1 > $pctlr[$ctrl]['segments']) {
                        $action = $pctlr[$ctrl]['command'];
                    }
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
                        break;
                    case 404:
                    case 500:
                    case 503:
                        Errors::displayError($action);
                        break;
                }
            }
        }
    }

    /**
     *  \brief Retorna o nome da classe da controller.
     *
     *  \return O nome da classe da controller
     */
    public static function getControllerClass()
    {
        return self::$class_controller;
    }

    /**
     *  \brief Retorna a URI atual.
     *
     *  \return A string da URI
     */
    public static function getURIString()
    {
        return self::$uri_string;
    }

    /**
     *  \brief Retorna a página atual.
     *
     *  \return O segmento que representa a página atual
     */
    public static function currentPage()
    {
        return self::getSegment(self::$segment_page, false);
    }

    /**
     *  \brief Retorna o caminho relativo da página atual.
     *
     *  \return Uma string contendo o caminho relativo à página atual
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
     *  \brief Retorna a URI da página atual.
     *
     *  \returns Uma string contendo a URI da página atual
     */
    public static function currentPageURI()
    {
        return trim(str_replace(DIRECTORY_SEPARATOR, '/', self::relativePathPage()).'/'.self::currentPage(), '/');
    }

    /**
     *  \brief Define o segmento relativo à página atual.
     *
     *  \param[in] $segment_num número relativo ao segmento da URI
     *  \return \c trus se definiu o segmento relativo à página atual e \c false em caso contrário
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
     *  \brief Retorna o segmento da URI selecionado.
     *
     *  \param[in] $segment_num O número do segmento desejado.
     *  \param[in] $relative_to_page Flag (true/false) que determina se o segmento desejado é
     *  	relativo ao segmento que determina a página atual. Default = true.
     *  \return o valor do segmento ou \c false caso o segmento não exista.
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
            return self::$segments[ $segment_num ];
        }

        return false;
    }

    /**
     *  \brief Retorna o segmento ignorado da URI selecionado.
     *
     *  \param[in] $segment_num O número do segmento desejado
     *  \return o valor do segmento ignorado ou \c false caso o segmento não exista
     */
    public static function getIgnoredSegment($segment_num)
    {
        if (array_key_exists($segment_num, self::$ignored_segments)) {
            return self::$ignored_segments[ $segment_num ];
        }

        return false;
    }

    /**
     *  \brief Retorna todos os segmentos.
     *
     *  \return um array contendo todos os segmentos
     */
    public static function getAllSegments()
    {
        return self::$segments;
    }

    /**
     *  \brief Retorna todos os segmentos ignorados.
     *
     *  \return um array contendo todos os segmentos ignorados
     */
    public static function getAllIgnoredSegments()
    {
        return self::$ignored_segments;
    }

    /**
     *  \brief Adiciona um novo segmento de URI.
     *
     *  \param[in] $segment String contendo o valor do segmento
     *  \return \c true se tiver sucesso e \c false em caso contrário
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
     *  \brief Insere um novo segmento de URI.
     *
     *  \param[in] (int) $position Inteiro contendo a posição de inserção
     *  \param[in] (string) $segment String contendo o valor do segmento
     *  \return \c true se tiver sucesso e \c false em caso contrário
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
     *  \brief Retorna o valor de um parâmetro GET.
     *
     *  \param[i] $var String contendo o nome da variável desesada
     *  \return O valor da variável, caso exista, ou \c false caso a variável não exista
     */
    public static function _GET($var)
    {
        if (array_key_exists($var, self::$get_params)) {
            return self::$get_params[ $var ];
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
     *  \brief retorna todo o _GET
     *  \see _GET.
     */
    public static function getParams()
    {
        return self::$get_params;
    }

    /**
     *  \brief Return the request method.
     */
    public static function requestMethod()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : ((PHP_SAPI === 'cli' || defined('STDIN')) ? 'CIL' : '');
    }

    /**
     *  \brief remove um parametro do _GET.
     *
     *  \param[in] $var String contendo o nome da variável a ser excluida
     */
    public static function removeParam($var)
    {
        unset(self::$get_params[$var]);
    }

    /**
     *  \brief Define o valor de um parâmetro.
     *
     *  \param[in] $var String contendo o nome da variável a ser definida
     *  \param[in] $value O valor da variável
     */
    public static function setParam($var, $value)
    {
        self::$get_params[ $var ] = $value;
    }

    /**
     *  \brief Monta uma URL.
     *
     *  \param[in] $segments Array contendo os segmentos da URI
     *  \param[in] $query Array contendo as variáveis a serem passadas via na URL GET
     *  \param[in] $forceRewrite flag (true/false) que determina se o formato SEF deve ser forçado
     *  \return Uma \c string contendo a URL
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
            if ($segments[ $i ] != 'index' && $segments[ $i ] != '') {
                $uri .= (empty($uri) ? '' : '/').self::makeSlug($segments[ $i ], '-', '\.,|~\#', false);
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
     *  \brief Pega o host acessado.
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
     *  \brief Manda o header de redirecionamento para uma URL.
     *
     *  Este método envia o cabeçalho (header) de redirecionamento para o usuário e termina a
     *  execução do sistema.
     *
     *  \param[in] $url A URL para qual o usuário deve ser redirecionado
     *  \param[in] $header Um inteiro com o código de redirecionamento
     *      (302 = permanente, 301 = temporário, etc.).\n
     *      Se omitido usa 302 por padrão.
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
     *  \brief Gera o slug de uma expressão.
     *
     *  \param[in] (string)$txt Expressão a ser convertida em slug.
     *  \paran[in] (string)$space Caracter que será usado para substituir os espaços na expressão. Se omitodo será usado o caracter "-" como padrão.
     *  \param[in] (string)$accept String contendo relação de caracteres também aceitos para montagem do slug.
     *      Essa sting será usada numa expressão regular, então alguns caracteres como o ponto, precisam ser escapados.
     *      Se nenhum caracter for informado, apenas letras, números e os caracteres "_" e "-" serão aceitos. Todos os demais serão removidos.
     *  \param[in] (bool)$lowercase Converte para letras minúsculas.
     *  \return Uma string com o slug
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
     *  \brief Informa se a requisição recebida foi um Ajax.
     *
     *  Verifica se a requisição recebida contém HTTP_X_REQUESTED_WITH no cabeçalho do pacote.
     *  Test to see if a request contains the HTTP_X_REQUESTED_WITH header.
     *
     *  @return (bool) Retorna true ou false
     */
    public static function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
