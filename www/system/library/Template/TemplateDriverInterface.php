<?php
/** \file
 *  Springy.
 *
 *  \brief      Interface para driver de tratamento de templates.
 *  \copyright  ₢ 2007-2016 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \version    1.1.0.3
 *  \ingroup    framework
 */
namespace Springy\Template;

/**
 *  \brief Interface para driver de renderização de templates.
 *
 *  \note Esta classe é uma interface para construção de classes utilizadas como drivers
 *        para utilização de classes de renderização de template HTML.
 */
interface TemplateDriverInterface
{
    // const TPL_NAME_SUFIX = '.tpl.html';

    /**
     *  \brief Add an alternate path to the templates.
     */
    public function addTemplateDir($path);

    /**
     *  \brief Define o local dos arquivos de template.
     */
    public function setTemplateDir($path);

    /**
     *  \brief Define o local dos arquivos de template compilados.
     */
    public function setCompileDir($path);

    /**
     *  \brief Define o local dos arquivos .conf usados nas tpls.
     */
    public function setConfigDir($path);

    /**
     *  \brief Define o local dos arquivos de template cacheados.
     */
    public function setCacheDir($path);

    /**
     *  \brief Verifica se o template está cacheado.
     *
     * @return bool
     */
    public function isCached();

    /**
     *  \brief Define o cacheamento dos templates.
     *
     *  @
     */
    public function setCaching($value = 'current');

    public function setCacheLifetime($seconds);

    /**
     *  \brief Retorna a página montada.
     */
    public function fetch();

    /**
     *  \brief Define o arquivos de template.
     *
     * @param string $tpl Nome do template, sem extenção do arquivo
     */
    public function setTemplate($tpl);

    /**
     *  \brief Define o id do cache.
     */
    public function setCacheId($cid);

    /**
     *  \brief Define o id da compilação.
     */
    public function setCompileId($cid);

    /**
     *  \brief Define uma variável do template.
     */
    public function assign($var, $value = null, $nocache = false);

    /**
     *  \brief Método statico que define um pluguin para todas as instancias da Template.
     */
    public function registerPlugin($type, $name, $callback, $cacheable = null, $cache_attrs = null);

    /**
     *  \brief Limpa uma variável do template.
     */
    public function clearAssign($var);

    /**
     *  \brief clears the entire template cache.
     *
     *  As an optional parameter, you can supply a minimum age in seconds the cache files must be before they will get cleared.
     */
    public function clearAllCache($expire_time);

    /**
     *  \brief Limpa o cache para o template corrente.
     */
    public function clearCache($expireTime = null);

    /**
     *  \brief Limpa a versão compilada do template atual.
     */
    public function clearCompiled($expTime);

    /**
     *  \brief Limpa variável de config definida.
     */
    public function clearConfig($var);

    /**
     *  \brief Verifica se um arquivo de template existe.
     */
    public function templateExists($tplName);
}
