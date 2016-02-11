<?php
/**	\file
 *	FVAL PHP Framework for Web Applications.
 *
 *  \copyright	Copyright (c) 2007-2016 FVAL Consultoria e Informática Ltda.\n
 *  \copyright	Copyright (c) 2007-2016 Fernando Val\n
 *	\copyright	Copyright (c) 2009-2013 Lucas Cardozo
 *
 *	\brief		Classe estática para tratamento de templates
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	2.3.4
 *  \author		Lucas Cardozo - lucas.cardozo@gmail.com
 *	\ingroup	framework
 *  \deprecated
 *  \note       This class was deprecated. Change to Kernel methods. See each correspondent method below. This class will be removed in early future version.
 */
namespace FW;

/**
 *  \brief Classe estática para tratamento de templates.
 */
class Template_Static
{
    /**
     *	\brief Método statico que define um pluguin para todas as instancias da Template.
     *  \deprecated
     *  \note This class will be deleted on future version. Use Kernel::registerTemplateFunction($type, $name, $callback, $cacheable = null, $cacheAttrs = null).
     *  \see Kernel::registerTemplateFunction($type, $name, $callback, $cacheable = null, $cacheAttrs = null).
     */
    public static function registerDefaultPlugin($type, $name, $callback, $cacheable = null, $cache_attrs = null)
    {
        Kernel::registerTemplateFunction($type, $name, $callback, $cacheable, $cache_attrs);
    }

    /**
     *  \brief Método statico que adiciona uma variável a todas as instancias da Template.
     *  \deprecated
     *  \note This class will be deleted on future version. Use Kernel::assignTemplateVar($name, $value).
     *  \see Kernel::assignTemplateVar($name, $value).
     */
    public static function assignDefaultVar($name, $value)
    {

        Kernel::assignTemplateVar($name, $value);
    }

    /**
     *	\brief Método statico que retorna todas as variáveis registradas.
     *  \deprecated
     *  \note This class will be deleted on future version. Use Kernel::getTemplateVar().
     *  \see Kernel::getTemplateVar().
     */
    public static function getDefaultVars()
    {
        return Kernel::getTemplateVar();
    }

    /**
     *  \brief Método statico que retorna todos os Plugins registrados.
     *  \deprecated
     *  \note This class will be deleted on future version. Use Kernel::getTemplateFunctions().
     *  \see Kernel::getTemplateFunctions().
     */
    public static function getDefaultPlugins()
    {
        return Kernel::getTemplateFunctions();
    }

    /**
     *	\brief Método statico que retorna uma variável definida como padrão a todas as instancias da Template.
     *  \deprecated
     *  \note This class will be deleted on future version. Use Kernel::getTemplateVar($name).
     *  \see Kernel::getTemplateVar($name).
     */
    public static function getDefaultVar($name)
    {
        return Kernel::getTemplateVar($name);
    }
}
