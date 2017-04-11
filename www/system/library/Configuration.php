<?php
/**	\file
 *	Springy.
 *
 *	\brief      Application configuration handler.
 *  \copyright  ₢ 2007-2016 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \author     Allan Marques - allan.marques@ymail.com
 *	\version    3.0.0.12
 *	\ingroup    framework
 */

namespace Springy;

/**
 *  \brief Classe de configuração.
 *
 *  Esta classe é estática e invocada automaticamente pelo framework.
 */
class Configuration
{
    /// Array interno com dados de configuração
    private static $confs = [];

    const LC_DB = 'db';
    const LC_MAIL = 'mail';
    const LC_SYSTEM = 'system';
    const LC_TEMPLATE = 'template';
    const LC_URI = 'uri';

    /**
     *  \brief Pega o conteúdo de um registro de configuração.
     *
     *  \param[in] (string) $local - nome do arquivo de configuração
     *  \param[in] (string) $var - registro desejado
     *  \param[in] (string) $var - registro desejado.\n
     *      Se omitido, poderá ser utilizado o conceito de sub-níveis separedos por ponto.
     *  \return se o registro existir, retorna seu valor, caso contrário retorna NULL
     */
    public static function get($local, $var = null)
    {
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

        return Utils\ArrayUtils::newInstance()->dottedGet(self::$confs[$local], $var);
    }

    /**
     *  \brief Altera o valor de uma entrada de configuração.
     *
     *  Esta alteração é temporária e estará ativa apenas durante a execução da aplicação.
     *  Nenhuma alteração será feita nos arquivos de configuração.
     *
     *  \param[in] (string) $local - nome do arquivo de configuração
     *  \param[in] (string) $var - nome da entrada de configuração
     *  \param[in] (variant) $valor - novo valor da entrada de configuração
     *  \param[in] (string) $var - registro desejado.\n
     *      Se omitido, poderá ser utilizado o conceito de sub-níveis separedos por ponto.
     *      Nesse caso, $local receberá o local separado por pontos e $var o valor a ser armazenado.
     *  \return void
     */
    public static function set($local, $var, $value = null)
    {
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

        Utils\ArrayUtils::newInstance()->dottedSet(self::$confs[$local], $var, $value);
    }

    /**
     *  \bried Load the configuration file in JSON format.
     */
    private static function _loadJSON($file, $local)
    {
        if (!file_exists($file.'.json')) {
            return;
        }

        if (!$str = file_get_contents($file.'.json')) {
            new Errors(500, 'Can not open the configuration file '.$file.'.json');
        }

        $conf = json_decode($str, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            new Errors(500, 'Parse error at '.$file.'.json: '.json_last_error_msg());
        }

        self::$confs[$local] = array_replace_recursive(self::$confs[$local], $conf);
    }

    /**
     *  \brief Load the configuration file in PHP format.
     */
    private static function _loadPHP($file, $local)
    {
        if (!file_exists($file.'.conf.php')) {
            return;
        }

        $conf = [];

        require_once $file.'.conf.php';
        self::$confs[$local] = array_replace_recursive(self::$confs[$local], $conf);

        // Overwrite the configuration for a specific host
        if (!isset($over_conf)) {
            return;
        }

        $host = URI::http_host();

        if (!$host || !isset($over[$host])) {
            return;
        }

        self::$confs[$local] = array_replace_recursive(self::$confs[$local], $over[$host]);
    }

    /**
     *  \brief Load the configuration file to the local.
     */
    private static function _load($file, $local)
    {
        self::_loadPHP($file, $local);
        self::_loadJSON($file, $local);

        // Overwrite the configuration for a specific host, if exists
        if (!$host = URI::http_host()) {
            return;
        }

        self::_loadPHP($file.'-'.$host, $local);
        self::_loadJSON($file.'-'.$host, $local);
    }

    /**
     *	\brief Load a configuration for the given local.
     *
     *	\param[in] (string) $local - the name of the local.
     */
    public static function load($local)
    {
        self::$confs[$local] = [];

        // Load the default configuration file
        self::_load(Kernel::path(Kernel::PATH_CONFIGURATION).DS.$local.'.default', $local);

        // Load the configuration file for the current environment
        self::_load(Kernel::path(Kernel::PATH_CONFIGURATION).DS.Kernel::environment().DS.$local, $local);

        // Check if configuration was loaded
        if (empty(self::$confs[$local])) {
            new Errors(500, 'Settings for "'.$local.'" not found in the environment "'.Kernel::environment().'".');
        }
    }

    /**
     *  \brief Save the local configuration to a JSON file.
     */
    public static function save($local)
    {
        $fileName = Kernel::path(Kernel::PATH_CONFIGURATION).DS.Kernel::environment().DS.$local.'.json';

        if (!file_put_contents($fileName, json_encode(self::$confs[$local]))) {
            new Errors(500, 'Can not write to '.$fileName);
        }
    }
}
