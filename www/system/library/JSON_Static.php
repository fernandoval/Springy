<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *	\copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *	\copyright Copyright (c) 2009-2013 Lucas Cardozo
 *
 *	\brief		Classe stática para tratamento JSON
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	1.0.0
 *  \author		Lucas Cardozo - lucas.cardozo@gmail.com
 *	\ingroup	framework
 */

class JSON_Static {
	private static $defaultVars = array();

	/**
	 *	\brief Método statico que adiciona uma variável a todas as instancias do JSON
	 */
	public static function addDefaultVar($name, $value) {
		JSON_Static::$defaultVars[$name] = $value;
	}

	/**
	 *	\brief Método statico que retorna todas as variáveis registradas
	 */
	public static function getDefaultVars() {
		return self::$defaultVars;
	}

	/**
	 *	\brief Método statico que retorna uma variável definida como padrão a todas as instancias da Template
	 *
	 *	\param[in] String $name
	 *
	 *	\return mixed
	 */
	public static function getDefaultVar($name) {
		return (isset(JSON_Static::$defaultVars[$name]) ? JSON_Static::$defaultVars[$name] : NULL);
	}
}