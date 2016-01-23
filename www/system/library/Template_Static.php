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
 *	\version	1.1.2
 *  \author		Lucas Cardozo - lucas.cardozo@gmail.com
 *	\ingroup	framework
 */
namespace FW;

/**
 *  \brief Classe estática para tratamento de templates.
 */
class Template_Static
{
    private static $defaultVars = [];
    private static $defaultFuncs = [];

    /**
     *	\brief Método statico que define um pluguin para todas as instancias da Template.
     */
    public static function registerDefaultPlugin($type, $name, $callback, $cacheable = null, $cache_attrs = null)
    {
        self::$defaultFuncs[] = [$type, $name, $callback, $cacheable, $cache_attrs];
    }

    /**
     *	\brief Método statico que adiciona uma variável a todas as instancias da Template.
     */
    public static function assignDefaultVar($name, $value)
    {
        self::$defaultVars[$name] = $value;
    }

    /**
     *	\brief Método statico que retorna todas as variáveis registradas.
     *
     * @return array
     */
    public static function getDefaultVars()
    {
        return self::$defaultVars;
    }

    /**
     *	\brief Método statico que retorna todos os Plugins registrados.
     *
     * @return array
     */
    public static function getDefaultPlugins()
    {
        return self::$defaultFuncs;
    }

    /**
     *	\brief Método statico que retorna uma variável definida como padrão a todas as instancias da Template.
     *
     * @param[in] String $name
     *
     * @return mixed
     */
    public static function getDefaultVar($name)
    {
        return isset(self::$defaultVars[$name]) ? self::$defaultVars[$name] : null;
    }
}
