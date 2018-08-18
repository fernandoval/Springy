<?php
/** \file
 *  Springy.
 *
 *  \brief      Classe stática para tratamento JSON.
 *  \copyright  Copyright (c) 2009-2013 Lucas Cardozo
 *  \author     Lucas Cardozo - lucas.cardozo@gmail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    1.2.2
 *  \ingroup    framework
 */

namespace Springy\Utils;

/**
 *  \brief Classe stática para tratamento JSON.
 */
class JSON_Static
{
    private static $defaultVars = [];

    /**
     *	\brief Método statico que adiciona uma variável a todas as instancias do JSON.
     */
    public static function addDefaultVar($name, $value)
    {
        self::$defaultVars[$name] = $value;
    }

    /**
     *	\brief Método statico que retorna todas as variáveis registradas.
     */
    public static function getDefaultVars()
    {
        return self::$defaultVars;
    }

    /**
     *	\brief Método statico que retorna uma variável definida como padrão a todas as instancias da Template.
     *
     *	\param[in] String $name
     *
     *	\return mixed
     */
    public static function getDefaultVar($name)
    {
        return isset(self::$defaultVars[$name]) ? self::$defaultVars[$name] : null;
    }
}
