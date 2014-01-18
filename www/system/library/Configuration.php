<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *	\copyright Copyright (c) 2007-2014 FVAL Consultoria e Informática Ltda.
 *	\copyright Copyright (c) 2007-2014 Fernando Val
 *
 *	\brief		Classe de configuração
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	1.1.1
 *  \author		Fernando Val  - fernando.val@gmail.com
 *	\ingroup	framework
 */

namespace FW;

/**
 *  \brief Classe de configuração
 *  
 *  Esta classe é estática e invocada automaticamente pelo framework.
 */
class Configuration {
	/// Array interno com dados de configuração
	private static $confs = array();


	/**
	 *	\brief Pega o conteúdo de um registro de configuração
	 *
	 *	\param[in] (string) $local - nome do arquivo de configuração
	 *	\param[in] (string) $var - registro desejado
	 *	\return se o registro existir, retorna seu valor, caso contrário retorna NULL
	 */
	public static function get($local, $var) {
		if (!isset(self::$confs[$local])) {
			self::load($local);
		}
		return (isset(self::$confs[$local][$var]) ? self::$confs[$local][$var] : NULL);
	}

	/**
	 *	\brief Altera o valor de uma entrada de configuração
	 *
	 *	\param[in] (string) $local - nome do arquivo de configuração
	 *	\param[in] (string) $val - nome da entrada de configuração
	 *	\param[in] (variant) $valor - novo valor da entrada de configuração
	 *	\return void
	 */
	public static function set($local, $var, $value) {
		self::$confs[$local][$var] = $value;
	}

	/**
	 *  \brief Carrega o arquivo de configuração e seta o atributo de configuração
	 */
	private static function _load($config_file, $local) {
		if (file_exists($config_file)) {
			$conf = array();
			require_once $config_file;
			self::$confs[ $local ] = array_merge(self::$confs[ $local ], $conf);
			
			return true;
		}
		
		return false;
	}
	
	/**
	 *	\brief Carrega um arquivo de configuração
	 *
	 *	\param[in] (string) $local - nome do arquivo de configuração
	 *	\return \c true se tiver carregado o arquivo de configuração ou \c false em caso contrário
	 */
	public static function load($local) {
		self::$confs[ $local ] = array();
	
		// Carrega a configuração DEFAULT para $local
		self::_load($GLOBALS['SYSTEM']['CONFIG_PATH'] . DIRECTORY_SEPARATOR . $local . '.default.conf.php', $local);
		
		// Define o arquivo de configuração para o ambiente ativo
		if (empty($GLOBALS['SYSTEM']['ACTIVE_ENVIRONMENT'])) {
			if (isset($_SERVER['HTTP_HOST'])) {
				$environment = $_SERVER['HTTP_HOST'];
			} else {
				$environment = 'unknowed';
			}
		} else {
			$environment = $GLOBALS['SYSTEM']['ACTIVE_ENVIRONMENT'];
		}
		if (is_array($GLOBALS['SYSTEM']['ENVIRONMENT_ALIAS']) && count($GLOBALS['SYSTEM']['ENVIRONMENT_ALIAS'])) {
			foreach($GLOBALS['SYSTEM']['ENVIRONMENT_ALIAS'] as $alias => $as) {
				if (preg_match('/^' . $alias . '$/', $environment)) {
					$environment = $as;
				}
			}
		}
		
		// Carreta a configuração para o ambiente ativo
		self::_load($GLOBALS['SYSTEM']['CONFIG_PATH'] . DIRECTORY_SEPARATOR . $environment . DIRECTORY_SEPARATOR . $local . '.conf.php', $local);
		
		// Confere se a configuração foi carregada
		if (empty(self::$confs[ $local ])) {
			Errors::displayError(500, 'Missing configuration for "' . $local . '" on environment "' . $environment . '".');
		}
	
		return true;
	}
}