<?php
/**
 * Class driver for Smarty template engine.
 *
 * This class implements Smarty template engine inside Springy\Template.
 *
 * @see       http://www.smarty.net/
 *
 * @copyright 2014-2018 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   1.7.17
 */

namespace Springy\Template;

use Springy\Configuration;
use Springy\Errors;
use Springy\Kernel;
use Springy\URI;

/**
 * Class driver for Smarty template engine.
 *
 * This class is a driver for Springy\Template class and uses the
 * Smarty template engine.
 */
class SmartyDriver implements TemplateDriverInterface
{
    const TPL_NAME_SUFIX = '.tpl.html';

    /// Internal template object
    private $tplObj = null;
    /// Template name
    private $templateName = null;
    /// Template cache identifier
    private $templateCacheId = null;
    /// Template compile identifier
    private $templateCompileId = null;
    /// Template variables
    private $templateVars = [];
    /// Template plugins
    private $templateFuncs = [];

    /**
     * Constructor.
     *
     * Initializes the Smarty class.
     *
     * @param string|array $tpl
     */
    public function __construct($tpl = null)
    {
        $this->tplObj = new \Smarty();

        if (Configuration::get('template', 'strict_variables')) {
            $this->tplObj->error_reporting = E_ALL & ~E_NOTICE;
            \Smarty::muteExpectedErrors();
        }
        $this->tplObj->debugging = Configuration::get('template', 'debug');
        $this->tplObj->debugging_ctrl = Configuration::get('template', 'debugging_ctrl');
        $this->tplObj->use_sub_dirs = Configuration::get('template', 'use_sub_dirs');

        $this->setCacheDir(Configuration::get('template', 'template_cached_path'));
        $this->setTemplateDir([
            'main'    => Configuration::get('template', 'template_path'),
            'default' => Configuration::get('template', 'default_template_path'),
        ]);
        $this->setCompileDir(Configuration::get('template', 'compiled_template_path'));
        $this->setConfigDir(Configuration::get('template', 'template_config_path'));
        $this->tplObj->addPluginsDir(Configuration::get('template', 'template_plugins_path'));
        $this->setTemplate($tpl);

        // Iniciliza as variáveis com URLs padrão de template
        if (Configuration::get('uri', 'common_urls')) {
            if (!Configuration::get('uri', 'register_method_set_common_urls')) {
                foreach (Configuration::get('uri', 'common_urls') as $var => $value) {
                    if (isset($value[4])) {
                        $this->assign($var, URI::buildURL($value[0], $value[1], $value[2], $value[3], $value[4]));
                    } elseif (isset($value[3])) {
                        $this->assign($var, URI::buildURL($value[0], $value[1], $value[2], $value[3]));
                    } elseif (isset($value[2])) {
                        $this->assign($var, URI::buildURL($value[0], $value[1], $value[2]));
                    } elseif (isset($value[1])) {
                        $this->assign($var, URI::buildURL($value[0], $value[1]));
                    } else {
                        $this->assign($var, URI::buildURL($value[0]));
                    }
                }
            } elseif (Configuration::get('uri', 'register_method_set_common_urls')) {
                $toCall = Configuration::get('uri', 'register_method_set_common_urls');
                if ($toCall['static']) {
                    if (!isset($toCall['method'])) {
                        throw new \Exception('You need to determine which method will be executed.', 500);
                    }

                    //$toCall['class']::$toCall['method'];
                } else {
                    $obj = new $toCall['class']();
                    if (isset($toCall['method']) && $toCall['method']) {
                        $obj->$toCall['method'];
                    }
                }
            }
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        unset($this->tplObj);
    }

    /**
     * Adds an alternate path to the templates folder.
     *
     * @param mixed $path path in the file system.
     *
     * @return void
     */
    public function addTemplateDir($path)
    {
        $this->tplObj->addTemplateDir($path);
    }

    /**
     * Sets the path to the template folder.
     *
     * @param mixed $path path in the file system.
     *
     * @return void
     */
    public function setTemplateDir($path)
    {
        $this->tplObj->setTemplateDir($path);
    }

    /**
     * Defines the compiled template folder path.
     *
     * @param mixed $path path in the file system.
     *
     * @return void
     */
    public function setCompileDir($path)
    {
        $this->tplObj->setCompileDir($path);
    }

    /**
     * Defines the folder path of the configuration files for templates.
     *
     * @param mixed $path path in the file system.
     *
     * @return void
     */
    public function setConfigDir($path)
    {
        $this->tplObj->setConfigDir($path);
    }

    /**
     * Sets the template cache folder path.
     *
     * @param mixed $path path in the file system.
     *
     * @return void
     */
    public function setCacheDir($path)
    {
        $this->tplObj->setCacheDir($path);
    }

    /**
     * Checks if the template is cached.
     *
     * @return bool
     */
    public function isCached()
    {
        return $this->tplObj->isCached($this->templateName . self::TPL_NAME_SUFIX, $this->templateCacheId, $this->templateCompileId);
    }

    /**
     * Defines template caching.
     *
     * @param string $value
     *
     * @return void
     */
    public function setCaching($value = 'current')
    {
        $this->tplObj->setCaching($value != 'current' ? \Smarty::CACHING_LIFETIME_SAVED : \Smarty::CACHING_LIFETIME_CURRENT);
    }

    /**
     * Sets the template cache lifetime.
     *
     * @param int $seconds
     *
     * @return void
     */
    public function setCacheLifetime($seconds)
    {
        $this->tplObj->setCacheLifetime($seconds);
    }

    /**
     * Returns the template output.
     *
     * @return string
     */
    public function fetch()
    {
        if (!$this->templateExists($this->templateName)) {
            new Errors(404, $this->templateName . self::TPL_NAME_SUFIX);
        }

        // Alimenta as variáveis CONSTANTES
        $this->tplObj->assign('HOST', URI::buildURL());
        $this->tplObj->assign('CURRENT_PAGE_URI', URI::currentPageURI());
        $this->tplObj->assign('SYSTEM_NAME', Kernel::systemName());
        $this->tplObj->assign('SYSTEM_VERSION', Kernel::systemVersion());
        $this->tplObj->assign('PROJECT_CODE_NAME', Kernel::projectCodeName());
        $this->tplObj->assign('ACTIVE_ENVIRONMENT', Kernel::environment());

        // Alimenta as variáveis padrão da aplicação
        foreach (Kernel::getTemplateVar() as $name => $value) {
            $this->tplObj->assign($name, $value);
        }

        // Alimenta as variáveis do template
        foreach ($this->templateVars as $name => $data) {
            $this->tplObj->assign($name, $data['value'], $data['nocache']);
        }

        // Inicializa a função padrão assetFile
        $this->tplObj->registerPlugin('function', 'assetFile', [$this, 'assetFile']);

        // Inicializa as funções personalizadas padrão
        foreach (Kernel::getTemplateFunctions() as $func) {
            $this->tplObj->registerPlugin($func[0], $func[1], $func[2], $func[3], $func[4]);
        }

        // Inicializa as funções personalizadas do template
        foreach ($this->templateFuncs as $func) {
            $this->tplObj->registerPlugin($func[0], $func[1], $func[2], $func[3], $func[4]);
        }

        // if ( Configuration::get('template', 'debug') ) {
        //     $this->tplObj->muteExpectedErrors();
        //     $this->tplObj->display_debug( $this->tplObj );
        // }

        return $this->tplObj->fetch($this->templateName . self::TPL_NAME_SUFIX, $this->templateCacheId, $this->templateCompileId);
    }

    /**
     * Sets the template file.
     *
     * @param string $tpl name of the template, without file extension
     *
     * @return void
     */
    public function setTemplate($tpl)
    {
        // Se o nome do template não foi informado, define como relativo à controladora atual
        if ($tpl === null) {
            // Pega o caminho relativo da página atual
            $path = URI::relativePathPage(true);
            $this->setTemplate($path . (empty($path) ? '' : DIRECTORY_SEPARATOR) . URI::getControllerClass());

            return;
        }

        $this->templateName = ((is_array($tpl)) ? implode(DIRECTORY_SEPARATOR, $tpl) : $tpl);

        $compile = '';
        if (!is_null($tpl)) {
            $compile = is_array($tpl) ? implode(DIRECTORY_SEPARATOR, $tpl) : $tpl;
            $compile = substr($compile, 0, strrpos(DIRECTORY_SEPARATOR, $compile));
        }

        $this->setCompileDir(Configuration::get('template', 'compiled_template_path') . $compile);
    }

    /**
     * Sets the cache id.
     *
     * @param mixed $cid
     *
     * @return void
     */
    public function setCacheId($cid)
    {
        $this->templateCacheId = $cid;
    }

    /**
     * Sets the compile identifier.
     *
     * @param mixed $cid
     *
     * @return void
     */
    public function setCompileId($cid)
    {
        $this->templateCompileId = $cid;
    }

    /**
     * Assigns a variable to the template.
     *
     * @param string $var     the name of the variable.
     * @param mixed  $value   the value of the variable.
     * @param bool   $nocache (optional) if true, the variable is assigned as nocache variable.
     *
     * @return void
     */
    public function assign($var, $value = null, $nocache = false)
    {
        if (is_array($var)) {
            foreach ($var as $name => $value) {
                $this->assign($name, $value);
            }
        } else {
            $this->templateVars[$var] = ['value' => $value, 'nocache' => $nocache];
        }
    }

    /**
     * Registers custom functions or methods as template plugins.
     *
     * @param mixed        $type        defines the type of the plugin.
     * @param strin        $name        defines the name of the plugin.
     * @param string|array $callback    defines the callback.
     * @param mixed        $cacheable
     * @param mixed        $cache_attrs
     *
     * @return void
     */
    public function registerPlugin($type, $name, $callback, $cacheable = null, $cache_attrs = null)
    {
        $this->templateFuncs[] = [$type, $name, $callback, $cacheable, $cache_attrs];
    }

    /**
     * Clears the value of an assigned variable.
     *
     * @param string $var the name of the variable.
     *
     * @return void
     */
    public function clearAssign($var)
    {
        unset($this->tplVars[$var]);
    }

    /**
     * Clears the entire template cache.
     *
     * As an optional parameter, you can supply a minimum age in seconds the cache files must be before they will get cleared.
     */
    public function clearAllCache($expire_time)
    {
        $this->tplObj->clearAllCache($expire_time);
    }

    /**
     * Clears the cache of the template.
     *
     * @param int $expireTime only compiled templates older than exp_time seconds are cleared.
     *
     * @todo Implement cache and compiled identifiers.
     */
    public function clearCache($expireTime = null)
    {
        $this->tplObj->clearCache($this->templateName . self::TPL_NAME_SUFIX, $this->templateCacheId, $this->templateCompileId, $expireTime);
    }

    /**
     * Clears the compiled version of the template.
     *
     * @param int $expTime only compiled templates older than exp_time seconds are cleared.
     *
     * @todo Implement compiled identifier.
     */
    public function clearCompiled($expTime)
    {
        $this->tplObj->clearCompiledTemplate($this->templateName . self::TPL_NAME_SUFIX, $this->templateCompileId, $expTime);
    }

    /**
     *  \brief Limpa variável de config definida.
     */
    public function clearConfig($var)
    {
        $this->tplObj->clearConfig($var);
    }

    /**
     * Checks whether the specified template exists.
     *
     * @param string $tplName name of the template, without file extension
     *
     * @return bool
     */
    public function templateExists($tplName)
    {
        return $this->tplObj->templateExists($tplName . self::TPL_NAME_SUFIX);
    }

    /**
     * Mascara nome de arquivo estático para evitar cache do navegador.
     *
     * Este método é inserido como função de template para utilização na criação da URI
     * de arquivos estáticos de CSS e JavaScript com objetivo de evitar que o cache
     * do navegador utilize versões desatualizadas deles.
     */
    public function assetFile($params, $smarty)
    {
        if (empty($params['file'])) {
            return '#';
        }

        $srcPath = Configuration::get('system', 'assets_source_path') . DIRECTORY_SEPARATOR . $params['file'];
        $filePath = Configuration::get('system', 'assets_path') . DIRECTORY_SEPARATOR . $params['file'];
        $fileURI = Configuration::get('uri', 'assets_dir') . '/' . $params['file'];
        $get = [];

        if (file_exists($srcPath) && (!file_exists($filePath) || filemtime($filePath) < filemtime($srcPath))) {
            minify($srcPath, $filePath);
        }

        if (file_exists($filePath)) {
            $get['v'] = filemtime($filePath);
        }

        return URI::buildURL(explode('/', $fileURI), $get, isset($_SERVER['HTTPS']), empty($params['host']) ? 'static' : $params['host'], false);
    }
}
