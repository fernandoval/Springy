<?php
/**
 * Template handler class.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Lucas Cardozo <lucas.cardozo@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   4.2.14
 */

namespace Springy;

/**
 * Template handler class.
 *
 * This class uses template drivers as defined in configuration.
 */
class Template
{
    const TPL_ENGINE_SMARTY = 'smarty';
    const TPL_ENGINE_TWIG = 'twig';

    private $tplObj = null;

    /**
     * Constructor method.
     *
     * @param array|string|null $tpl the template name or path
     */
    public function __construct($tpl = null)
    {
        if (!$driver = Configuration::get('template', 'template_engine')) {
            $driver = self::TPL_ENGINE_SMARTY;
        }

        // Inicializa a classe de template
        switch (strtolower($driver)) {
            case self::TPL_ENGINE_SMARTY:
                $this->tplObj = new Template\SmartyDriver($tpl);
                break;
            case self::TPL_ENGINE_TWIG:
                $this->tplObj = new Template\TwigDriver($tpl);
                break;
            default:
                new Errors('500', 'Template engine not implemented');
        }
    }

    /**
     * Destruction method.
     */
    public function __destruct()
    {
        unset($this->tplObj);
    }

    /**
     * Sets the path to the template folder.
     *
     * @param string $path path in the file system.
     *
     * @return void
     */
    public function setTemplateDir($path): void
    {
        $this->tplObj->setTemplateDir($path);
    }

    /**
     * Defines the compiled template folder path.
     *
     * @param string $path path in the file system.
     *
     * @return void
     */
    public function setCompileDir($path): void
    {
        $this->tplObj->setCompileDir($path);
    }

    /**
     * Defines the folder path of the configuration files for templates.
     *
     * @param string $path path in the file system.
     *
     * @return void
     */
    public function setConfigDir($path): void
    {
        $this->tplObj->setConfigDir($path);
    }

    /**
     * Sets the template cache folder path.
     *
     * @param string $path path in the file system.
     *
     * @return void
     */
    public function setCacheDir($path): void
    {
        $this->tplObj->setCacheDir($path);
    }

    /**
     * Checks if the template is cached.
     *
     * @return bool
     */
    public function isCached(): bool
    {
        return $this->tplObj->isCached();
    }

    /**
     * Defines template caching.
     *
     * @param string $value
     *
     * @return void
     */
    public function setCaching($value = 'current'): void
    {
        $this->tplObj->setCaching($value);
    }

    /**
     * Sets the template cache lifetime.
     *
     * @param int $seconds
     *
     * @return void
     */
    public function setCacheLifetime($seconds): void
    {
        $this->tplObj->setCacheLifetime($seconds);
    }

    /**
     * Returns the template output.
     *
     * @return string
     */
    public function fetch(): string
    {
        return $this->tplObj->fetch();
    }

    /**
     * Sent the parsed template to default output device.
     *
     * @return void
     */
    public function display(): void
    {
        echo $this->tplObj->fetch();
    }

    /**
     * Sets the template file.
     *
     * @param string $tpl name of the template, without file extension.
     *
     * @return void
     */
    public function setTemplate($tpl): void
    {
        $this->tplObj->setTemplate($tpl);
    }

    /**
     * Sets the cache id.
     *
     * @param string $id
     *
     * @return void
     */
    public function setCacheId($id): void
    {
        $this->tplObj->setCacheId($id);
    }

    /**
     * Sets the compile identifier.
     *
     * @param string $id
     *
     * @return void
     */
    public function setCompileId($id): void
    {
        $this->tplObj->setCompileId($id);
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
    public function assign($var, $value = null, $nocache = false): void
    {
        $this->tplObj->assign($var, $value, $nocache);
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
    public function registerPlugin($type, $name, $callback, $cacheable = null, $cache_attrs = null): void
    {
        $this->tplObj->registerPlugin($type, $name, $callback, $cacheable, $cache_attrs);
    }

    /**
     * Clears the value of an assigned variable.
     *
     * @param string $var the name of the variable.
     *
     * @return void
     */
    public function clearAssign($var): void
    {
        $this->tplObj->clearAssign($var);
    }

    /**
     * Clears the entire template cache.
     *
     * As an optional parameter, you can supply a minimum age in seconds the cache files must be before they will get cleared.
     *
     * @param int $expire_time
     *
     * @return void
     */
    public function clearAllCache($expire_time): void
    {
        $this->tplObj->clearAllCache($expire_time);
    }

    /**
     * Clears the cache of the template.
     *
     * @param int $expireTime only compiled templates older than exp_time seconds are cleared.
     *
     * @return void
     */
    public function clearCache($expireTime = null): void
    {
        $this->tplObj->clearCache($expireTime);
    }

    /**
     * Clears the compiled version of the template.
     *
     * @param int $expTime only compiled templates older than exp_time seconds are cleared.
     *
     * @return void
     */
    public function clearCompiled($expTime): void
    {
        $this->tplObj->clearCompiled($expTime);
    }

    /**
     * Clears a configuration template variable.
     *
     * @param string $var
     *
     * @return void
     */
    public function clearConfig($var): void
    {
        $this->tplObj->clearConfig($var);
    }

    /**
     * Checks whether the specified template exists.
     *
     * @param string $tplName name of the template, without file extension.
     *
     * @return bool
     */
    public function templateExists($tplName): bool
    {
        if ($this->tplObj->templateExists($tplName)) {
            return true;
        }

        $this->tplObj->addTemplateDir(Configuration::get('template', 'default_template_path'));

        return $this->tplObj->templateExists($tplName);
    }

    /**
     * Returns the internal template object.
     *
     * @return object|null
     */
    public function templateObject()
    {
        return $this->tplObj;
    }
}
