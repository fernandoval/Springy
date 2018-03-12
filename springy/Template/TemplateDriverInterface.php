<?php
/**
 * Interface for template plugin drivers.
 *
 * This class is an interface for building drivers for interaction
 * with template plugins.
 *
 * @copyright 2015-2018 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   1.1.0.5
 */

namespace Springy\Template;

/**
 * Interface for template plugin drivers.
 */
interface TemplateDriverInterface
{
    // const TPL_NAME_SUFIX = '.tpl.html';

    /**
     * Adds an alternate path to the templates folder.
     *
     * @param mixed $path path in the file system.
     *
     * @return void
     */
    public function addTemplateDir($path);

    /**
     * Sets the path to the template folder.
     *
     * @param mixed $path path in the file system.
     *
     * @return void
     */
    public function setTemplateDir($path);

    /**
     * Defines the compiled template folder path.
     *
     * @param mixed $path path in the file system.
     *
     * @return void
     */
    public function setCompileDir($path);

    /**
     * Defines the folder path of the configuration files for templates.
     *
     * @param mixed $path path in the file system.
     *
     * @return void
     */
    public function setConfigDir($path);

    /**
     * Sets the template cache folder path.
     *
     * @param mixed $path path in the file system.
     *
     * @return void
     */
    public function setCacheDir($path);

    /**
     * Checks if the template is cached.
     *
     * @return bool
     */
    public function isCached();

    /**
     * Defines template caching.
     *
     * @param string $value
     *
     * @return void
     */
    public function setCaching($value = 'current');

    /**
     * Sets the template cache lifetime.
     *
     * @param int $seconds
     *
     * @return void
     */
    public function setCacheLifetime($seconds);

    /**
     * Returns the template output.
     *
     * @return string
     */
    public function fetch();

    /**
     * Sets the template file.
     *
     * @param string $tpl name of the template, without file extension
     *
     * @return void
     */
    public function setTemplate($tpl);

    /**
     * Sets the cache id.
     *
     * @param mixed $cid
     *
     * @return void
     */
    public function setCacheId($cid);

    /**
     * Sets the compile identifier.
     *
     * @param mixed $cid
     *
     * @return void
     */
    public function setCompileId($cid);

    /**
     * Assigns a variable to the template.
     *
     * @param string $var     the name of the variable.
     * @param mixed  $value   the value of the variable.
     * @param bool   $nocache (optional) if true, the variable is assigned as nocache variable.
     *
     * @return void
     */
    public function assign($var, $value = null, $nocache = false);

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
    public function registerPlugin($type, $name, $callback, $cacheable = null, $cache_attrs = null);

    /**
     * Clears the value of an assigned variable.
     *
     * @param string $var the name of the variable.
     *
     * @return void
     */
    public function clearAssign($var);

    /**
     * Clears the entire template cache.
     *
     * As an optional parameter, you can supply a minimum age in seconds the cache files must be before they will get cleared.
     */
    public function clearAllCache($expire_time);

    /**
     * Clears the cache of the template.
     *
     * @param int $expireTime only compiled templates older than exp_time seconds are cleared.
     *
     * @todo Implement cache and compiled identifiers.
     */
    public function clearCache($expireTime = null);

    /**
     * Clears the compiled version of the template.
     *
     * @param int $expTime only compiled templates older than exp_time seconds are cleared.
     *
     * @todo Implement compiled identifier.
     */
    public function clearCompiled($expTime);

    /**
     *  \brief Limpa variável de config definida.
     */
    public function clearConfig($var);

    /**
     * Checks whether the specified template exists.
     *
     * @param string $tplName name of the template, without file extension
     *
     * @return bool
     */
    public function templateExists($tplName);
}
