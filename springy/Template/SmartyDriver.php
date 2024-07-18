<?php

/**
 * Class driver for Smarty template engine.
 *
 * This class implements Smarty template engine inside Springy\Template.
 *
 * @see       http://www.smarty.net/
 *
 * @copyright 2014 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy\Template;

use Smarty;
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
    public const TPL_NAME_SUFIX = '.tpl.html';

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
        $this->tplObj = new Smarty();

        if (!config_get('template.strict_variables')) {
            $this->tplObj->muteUndefinedOrNullWarnings();
        }

        $this->tplObj->debugging = config_get('template.debug');
        $this->tplObj->debugging_ctrl = config_get('template.debugging_ctrl');
        $this->tplObj->use_sub_dirs = config_get('template.use_sub_dirs');

        $this->setCacheDir(config_get('template.template_cached_path'));
        $this->setTemplateDir([
            'main' => config_get('template.template_path'),
            'default' => config_get('template.default_template_path'),
        ]);
        $this->setCompileDir(config_get('template.compiled_template_path'));
        $this->setConfigDir(config_get('template.template_config_path'));
        $this->tplObj->addPluginsDir(config_get('template.template_plugins_path'));
        $this->setTemplate($tpl);
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
        return $this->tplObj->isCached(
            $this->templateName . self::TPL_NAME_SUFIX,
            $this->templateCacheId,
            $this->templateCompileId
        );
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
        $this->tplObj->setCaching(
            $value != 'current' ? Smarty::CACHING_LIFETIME_SAVED : Smarty::CACHING_LIFETIME_CURRENT
        );
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
            throw_error(404, $this->templateName . self::TPL_NAME_SUFIX);
        }

        // Alimenta as variáveis CONSTANTES
        $this->tplObj->assign('HOST', URI::buildURL());
        $this->tplObj->assign('CURRENT_PAGE_URI', URI::currentPageURI());
        $this->tplObj->assign('SYSTEM_NAME', app_name());
        $this->tplObj->assign('SYSTEM_VERSION', app_version());
        $this->tplObj->assign('PROJECT_CODE_NAME', app_codename());
        $this->tplObj->assign('ACTIVE_ENVIRONMENT', Kernel::environment());
        $this->tplObj->assign('APP_ENVIRONMENT', Kernel::environment());

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

        return $this->tplObj->fetch(
            $this->templateName . self::TPL_NAME_SUFIX,
            $this->templateCacheId,
            $this->templateCompileId
        );
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
            $path = implode(
                DS,
                array_filter(
                    array_merge(
                        Kernel::getTemplatePrefix(),
                        [URI::relativePathPage()]
                    )
                )
            );
            $this->setTemplate($path . (empty($path) ? '' : DS) . URI::getControllerClass());

            return;
        }

        $this->templateName = ((is_array($tpl)) ? implode(DS, $tpl) : $tpl);

        $compile = '';
        if (!is_null($tpl)) {
            $compile = is_array($tpl) ? implode(DS, $tpl) : $tpl;
            $compile = substr($compile, 0, strrpos(DS, $compile));
        }

        $this->setCompileDir(config_get('template.compiled_template_path') . $compile);
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
     * @param string       $name        defines the name of the plugin.
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
        unset($this->templateVars[$var]);
    }

    /**
     * Clears the entire template cache.
     *
     * As an optional parameter, you can supply a minimum age in seconds the
     * cache files must be before they will get cleared.
     */
    public function clearAllCache($expire_time)
    {
        $this->tplObj->clearAllCache($expire_time);
    }

    /**
     * Clears the cache of the template.
     *
     * @param int $expireTime only compiled templates older than exp_time
     *                        seconds are cleared.
     *
     * @todo Implement cache and compiled identifiers.
     */
    public function clearCache($expireTime = null)
    {
        $this->tplObj->clearCache(
            $this->templateName . self::TPL_NAME_SUFIX,
            $this->templateCacheId,
            $this->templateCompileId,
            $expireTime
        );
    }

    /**
     * Clears the compiled version of the template.
     *
     * @param int $expTime only compiled templates older than exp_time seconds
     *                     are cleared.
     *
     * @todo Implement compiled identifier.
     */
    public function clearCompiled($expTime)
    {
        $this->tplObj->clearCompiledTemplate(
            $this->templateName . self::TPL_NAME_SUFIX,
            $this->templateCompileId,
            $expTime
        );
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

        $srcPath = config_get('system.assets_source_path') . DS . $params['file'];
        $filePath = config_get('system.assets_path') . DS . $params['file'];
        $fileURI = config_get('uri.assets_dir') . '/' . $params['file'];
        $get = [];

        if (file_exists($srcPath) && (!file_exists($filePath) || filemtime($filePath) < filemtime($srcPath))) {
            minify($srcPath, $filePath);
        }

        if (file_exists($filePath)) {
            $get['v'] = filemtime($filePath);
        }

        return build_url(
            explode('/', $fileURI),
            $get,
            empty($params['host']) ? 'static' : $params['host'],
            false
        );
    }
}
