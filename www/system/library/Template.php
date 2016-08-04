<?php
/**	\file
 *	Springy.
 *
 *	\brief		Template handler class.
 *  \copyright	₢ 2007-2016 Fernando Val
 *  \author		Fernando Val - fernando.val@gmail.com
 *  \author		Lucas Cardozo - lucas.cardozo@gmail.com
 *	\version	4.1.1.12
 *	\ingroup	framework
 */
namespace Springy;

/**
 *  \brief Classe de tratamento de templates.
 *
 *  \note Esta classe utiliza os drivers de template conforme definido no arquivo de configuração.
 */
class Template
{
    const TPL_ENGINE_SMARTY = 'smarty';
    const TPL_ENGINE_TWIG = 'twig';

    private $tplObj = null;

    /**
     *	\brief Inicializa a classe de template.
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

        return true;
    }

    /**
     *  \brief Destrói o objeto.
     */
    public function __destruct()
    {
        unset($this->tplObj);
    }

    /**
     *	\brief Define o local dos arquivos de template.
     */
    public function setTemplateDir($path)
    {
        return $this->tplObj->setTemplateDir($path);
    }

    /**
     *	\brief Define o local dos arquivos de template compilados.
     */
    public function setCompileDir($path)
    {
        return $this->tplObj->setCompileDir($path);
    }

    /**
     *	\brief Define o local dos arquivos .conf usados nas tpls.
     */
    public function setConfigDir($path)
    {
        return $this->tplObj->setConfigDir($path);
    }

    /**
     *	\brief Define o local dos arquivos de template cacheados.
     */
    public function setCacheDir($path)
    {
        return $this->tplObj->setCacheDir($path);
    }

    /**
     *	\brief Verifica o template ideal de acordo com a página.
     */
    private function setAutoTemplatePaths()
    {
        return $this->tplObj->setAutoTemplatePaths();
    }

    /**
     *	\brief Verifica se o template está cacheado.
     *
     * @return bool
     */
    public function isCached()
    {
        return $this->tplObj->isCached();
    }

    /**
     *	\brief Define o cacheamento dos templates.
     *
     * @
     */
    public function setCaching($value = 'current')
    {
        return $this->tplObj->setCaching($value);
    }

    public function setCacheLifetime($seconds)
    {
        $this->tplObj->setCacheLifetime($seconds);
    }

    /**
     *	\brief Retorna a página montada.
     */
    public function fetch()
    {
        return $this->tplObj->fetch();
    }

    /**
     *	\brief Faz a saída da página montada.
     *
     * @return string
     */
    public function display()
    {
        echo $this->tplObj->fetch();
    }

    /**
     *	\brief Define o arquivos de template.
     *
     * @param string $tpl Nome do template, sem extenção do arquivo
     */
    public function setTemplate($tpl)
    {
        return $this->tplObj->setTemplate($tpl);
    }

    /**
     *	\brief Define o id do cache.
     */
    public function setCacheId($id)
    {
        return $this->tplObj->setCacheId($id);
    }

    /**
     *	\brief Define o id da compilação.
     */
    public function setCompileId($id)
    {
        return $this->tplObj->setCompileId($id);
    }

    /**
     *	\brief Define uma variável do template.
     */
    public function assign($var, $value = null, $nocache = false)
    {
        return $this->tplObj->assign($var, $value, $nocache);
    }

    /**
     *	\brief Método statico que define um pluguin para todas as instancias da Template.
     */
    public function registerPlugin($type, $name, $callback, $cacheable = null, $cache_attrs = null)
    {
        return $this->tplObj->registerPlugin($type, $name, $callback, $cacheable, $cache_attrs);
    }

    /**
     *	\brief Limpa uma variável do template.
     */
    public function clearAssign($var)
    {
        return $this->tplObj->clearAssign($var);
    }

    /**
     *	\brief clears the entire template cache.
     *
     *	As an optional parameter, you can supply a minimum age in seconds the cache files must be before they will get cleared.
     */
    public function clearAllCache($expire_time)
    {
        return $this->tplObj->clearAllCache($expire_time);
    }

    /**
     *	\brief Limpa o cache para o template corrente.
     */
    public function clearCache($expireTime = null)
    {
        return $this->tplObj->clearCache($expireTime);
    }

    /**
     *	\brief Limpa a versão compilada do template atual.
     */
    public function clearCompiled($expTime)
    {
        return $this->tplObj->clearCompiled($expTime);
    }

    /**
     *	\brief Limpa variável de config definida.
     */
    public function clearConfig($var)
    {
        return $this->tplObj->clearConfig($var);
    }

    /**
     *	\brief Verifica se um arquivo de template existe.
     */
    public function templateExists($tplName)
    {
        if ($this->tplObj->templateExists($tplName)) {
            return true;
        }

        $this->tplObj->addTemplateDir(Configuration::get('template', 'default_template_path'));

        return $this->tplObj->templateExists($tplName);
    }
}
