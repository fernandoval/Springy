<?php
/**
 * Class driver for Twig template engine.
 *
 * This class implements Twig template engine inside Springy\Template.
 *
 * @see       https://twig.symfony.com/
 *
 * @copyright 2014-2018 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   0.16.2.18
 */

namespace Springy\Template;

use Springy\Configuration;
use Springy\Errors;
use Springy\Kernel;
use Springy\URI;

/**
 * Class driver for Twig template engine.
 *
 * This class is a driver for Springy\Template class and uses the
 * Twig template engine.
 */
class TwigDriver implements TemplateDriverInterface
{
    const TPL_NAME_SUFIX = '.twig.html';

    /// Internal template object
    private $tplObj = null;
    /// Environment options
    private $envOptions = [];
    /// Template path
    private $templatePath = null;
    /// Template name
    private $templateName = null;
    /// Template cache identifier
    private $templateCacheId = null;
    /// Template compile identifier
    private $templateCompileId = null;
    /// Template variables
    private $templateVars = [];
    /// Template functions
    private $templateFuncs = [];

    /**
     * Constructor.
     *
     * Initializes the Twig instance.
     *
     * @param string|array $tpl
     */
    public function __construct($tpl = null)
    {
        // Inicializa a classe de template
        // \Twig_Autoloader::register();
        $this->envOptions = [
            'autoescape'       => Configuration::get('template', 'autoescape'),
            'strict_variables' => Configuration::get('template', 'strict_variables'),
            'debug'            => Configuration::get('template', 'debug'),
            'cache'            => Configuration::get('template', 'compiled_template_path'),
            'auto_reload'      => Configuration::get('template', 'auto_reload'),
            'optimizations'    => Configuration::get('template', 'optimizations'),
        ];

        $this->__twigInstance([
            Configuration::get('template', 'template_path'),
            Configuration::get('template', 'default_template_path'),
        ]);

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

        return true;
    }

    /**
     * Creates the Twig class instance.
     *
     * @param string|array $templatePath
     *
     * @return void
     */
    private function __twigInstance($templatePath)
    {
        if (isset($this->tplObj)) {
            unset($this->tplObj);
        }

        $this->templatePath = $templatePath;
        $loader = new \Twig_Loader_Filesystem($templatePath);
        $this->tplObj = new \Twig_Environment($loader, $this->envOptions);
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
        $this->tplObj->getLoader()->addPath($path);

        if (!is_array($path)) {
            $this->templatePath[] = $path;

            return;
        }

        $this->templatePath = array_merge($this->templatePath, $path);
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
        $this->__twigInstance($path);
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
        $this->envOptions['cache'] = $path;
        $this->__twigInstance($this->templatePath);
    }

    /**
     * Defines the folder path of the configuration files for templates.
     *
     * This method do nothing. Exists only by an interface requisition.
     *
     * @param mixed $path path in the file system.
     *
     * @return void
     */
    public function setConfigDir($path)
    {
        // Método criado apenas para atender definição da interface
    }

    /**
     * Sets the template cache folder path.
     *
     * This method do nothing. Exists only by an interface requisition.
     *
     * Twig cache dir and compiled dir is the same
     *
     * @param mixed $path path in the file system.
     *
     * @return void
     */
    public function setCacheDir($path)
    {
        // Do nothing
    }

    /**
     * Checks if the template is cached.
     *
     * @return bool
     */
    public function isCached()
    {
        return file_exists($this->tplObj->getCacheFilename($this->templateName . self::TPL_NAME_SUFIX));
    }

    /**
     * Defines template caching.
     *
     * This method do nothing. Exists only by an interface requisition.
     *
     * Twig does not support cached templates.
     *
     * @param string $value
     *
     * @return void
     */
    public function setCaching($value = 'current')
    {
        // Método criado apenas para atender definição da interface
    }

    /**
     * Sets the template cache lifetime.
     *
     * This method do nothing. Exists only by an interface requisition.
     *
     * Twig does not support cached templates.
     *
     * @param int $seconds
     *
     * @return void
     */
    public function setCacheLifetime($seconds)
    {
        // Método criado apenas para atender definição da interface
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
        $vars = [
            'HOST'               => URI::buildURL(),
            'CURRENT_PAGE_URI'   => URI::currentPageURI(),
            'SYSTEM_NAME'        => Kernel::systemName(),
            'SYSTEM_VERSION'     => Kernel::systemVersion(),
            'PROJECT_CODE_NAME'  => Kernel::projectCodeName(),
            'ACTIVE_ENVIRONMENT' => Kernel::environment(),
        ];

        // Alimenta as variáveis padrão da aplicação
        foreach (Kernel::getTemplateVar() as $name => $data) {
            $vars[$name] = $data;
        }

        // Alimenta as variáveis do template
        foreach ($this->templateVars as $name => $data) {
            $vars[$name] = $data['value'];
        }

        // Inicializa a função padrão assetFile
        $this->tplObj->addFunction(new \Twig_SimpleFunction('assetFile', [$this, 'assetFile']));

        // Inicializa as funções personalizadas padrão
        foreach (Kernel::getTemplateFunctions() as $func) {
            $this->tplObj->addFunction(new \Twig_SimpleFunction($func[1], $func[2]));
        }

        // Inicializa as funções personalizadas do template
        foreach ($this->templateFuncs as $func) {
            $this->tplObj->addFunction(new \Twig_SimpleFunction($func[1], $func[2]));
        }

        return $this->tplObj->render($this->templateName . self::TPL_NAME_SUFIX, $vars);
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
            $compile = is_array($tpl) ? implode('/', $tpl) : $tpl;
            $compile = substr($compile, 0, strrpos('/', $compile));
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

            return;
        }

        $this->templateVars[$var] = ['value' => $value, 'nocache' => $nocache];
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
     * This method do nothing. Exists only by an interface requisition.
     */
    public function clearAllCache($expire_time)
    {
        // Do nothing
    }

    /**
     * Clears the cache of the template.
     *
     * This method do nothing. Exists only by an interface requisition.
     *
     * @param int $expireTime only compiled templates older than exp_time seconds are cleared.
     */
    public function clearCache($expireTime = null)
    {
        // Do nothing
    }

    /**
     * Clears the compiled version of the template.
     *
     * This method do nothing. Exists only by an interface requisition.
     *
     * @param int $expTime only compiled templates older than exp_time seconds are cleared.
     */
    public function clearCompiled($expTime)
    {
        // Do nothing
    }

    /**
     *  \brief Limpa variável de config definida
     *  \note Esse método não tem funçao no Twig.
     */
    public function clearConfig($var)
    {
        // Método criado apenas para atender definição da interface
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
        return $this->tplObj->getLoader()->exists($tplName . self::TPL_NAME_SUFIX);
    }

    /**
     * Mascara nome de arquivo estático para evitar cache do navegador.
     *
     * Este método é inserido como função de template para utilização na criação da URI
     * de arquivos estáticos de CSS e JavaScript com objetivo de evitar que o cache
     * do navegador utilize versões desatualizadas deles.
     */
    public function assetFile($file, $host = 'static')
    {
        $srcPath = Configuration::get('system', 'assets_source_path') . DIRECTORY_SEPARATOR . $file;
        $filePath = Configuration::get('system', 'assets_path') . DIRECTORY_SEPARATOR . $file;
        $fileURI = Configuration::get('uri', 'assets_dir') . '/' . $file;
        $get = [];

        if (file_exists($srcPath) && (!file_exists($filePath) || filemtime($filePath) < filemtime($srcPath))) {
            minify($srcPath, $filePath);
        }

        if (file_exists($filePath)) {
            $get['v'] = filemtime($filePath);
        }

        return URI::buildURL(explode('/', $fileURI), $get, false, $host, false);
    }
}
