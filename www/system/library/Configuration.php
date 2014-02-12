<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *	\copyright Copyright (c) 2007-2014 FVAL Consultoria e Informática Ltda.
 *	\copyright Copyright (c) 2007-2014 Fernando Val
 *
 *	\brief		Classe de configuração
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	1.2.2
 *  \author		Fernando Val  - fernando.val@gmail.com
 *  \author		Allan Marques - allan.marques@ymail.com
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
	 *  \brief Pega o conteúdo de um registro de configuração
	 *
	 *  \param[in] (string) $local - nome do arquivo de configuração
	 *  \param[in] (string) $var - registro desejado
	 *  \param[in] (string) $var - registro desejado.\n
	 *      Se omitido, poderá ser utilizado o conceito de sub-níveis separedos por ponto.
	 *  \return se o registro existir, retorna seu valor, caso contrário retorna NULL
	 */
	public static function get($local, $var = null) {
        if (is_null($var)) {
            $firstSegment = substr($local, 0, strpos($local, '.'));

            if ($firstSegment) {
                $var = substr($local, strpos($local, '.') + 1);
                $local = $firstSegment;
            }
        }

		if (!isset(self::$confs[$local])) {
			self::load($local);
		}

        if (!$var) {
            return self::$confs[$local];
        }

		return ArrayUtils::newInstance()->dottedGet(self::$confs[$local], $var);
	}

	/**
	 *  \brief Altera o valor de uma entrada de configuração
	 *
	 *  \param[in] (string) $local - nome do arquivo de configuração
	 *  \param[in] (string) $var - nome da entrada de configuração
	 *  \param[in] (variant) $valor - novo valor da entrada de configuração
	 *  \param[in] (string) $var - registro desejado.\n
	 *      Se omitido, poderá ser utilizado o conceito de sub-níveis separedos por ponto.
	 *      Nesse caso, $local receberá o local separado por pontos e $var o valor a ser armazenado.
	 *  \return void
	 */
	public static function set($local, $var, $value = null) {
        if (is_null($value)) {
            $value = $var;
            $var = '';
            $firstSegment = substr($local, 0, strpos($local, '.'));

            if ($firstSegment) {
                $local = $firstSegment;
                $var = substr($local, strpos($local, '.') + 1);
            }

            if (!$var) {
                self::$confs[$local] = $value;
            }
        }

		ArrayUtils::newInstance()->dottedSet(self::$confs[$local], $var, $value);
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