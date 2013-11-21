<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *	\copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *	\copyright Copyright (c) 2007-2013 Fernando Val\n
 *	\copyright Copyright (c) 2009-2013 Lucas Cardozo
 *
 *	\brief		Classe estática para tratamento de templates
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	1.0.0
 *  \author		Lucas Cardozo - lucas.cardozo@gmail.com
 *	\ingroup	framework
 */

class Template_Static {
	private static $defaultVars = array();
	private static $defaultFuncs = array();

	/**
	 *	\brief Método statico que define um pluguin para todas as instancias da Template
	 */
	public static function registerDefaultPlugin($type, $name, $callback, $cacheable=NULL, $cache_attrs=NULL) {
		Template_Static::$defaultFuncs[] = array($type, $name, $callback, $cacheable, $cache_attrs);
	}

	/**
	 *	\brief Método statico que adiciona uma variável a todas as instancias da Template
	 */
	public static function assignDefaultVar($name, $value) {
		Template_Static::$defaultVars[$name] = $value;
	}

	/**
	 *	\brief Método statico que retorna todas as variáveis registradas
	 *
	 * @return Array
	 */
	public static function getDefaultVars() {
		return self::$defaultVars;
	}

	/**
	 *	\brief Método statico que retorna todos os Plugins registrados
	 *
	 * @return Array
	 */
	public static function getDefaultPlugins() {
		return self::$defaultFuncs;
	}

	/**
	 *	\brief Método statico que retorna uma variável definida como padrão a todas as instancias da Template
	 *
	 * @param[in] String $name
	 *
	 * @return mixed
	 */
	public static function getDefaultVar($name) {
		return (isset(Template_Static::$defaultVars[$name]) ? Template_Static::$defaultVars[$name] : NULL);
	}
}