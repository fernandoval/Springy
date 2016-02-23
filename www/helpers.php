<?php
/** \file
 *  FVAL PHP Framework for Web Applications.
 *  
 *  \copyright  Copyright (c) 2007-2016 FVAL Consultoria e Informática Ltda.\n
 *  \copyright  Copyright (c) 2007-2016 Fernando Val\n
 *
 *	\brief      Helper file - Functions and constants
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    2.0.1
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \ingroup    framework
 */

/*
 *  para deixar o desenvolvedor mais feliz e produtivo 
 */

/**------------------------------------------------------------
 *  Constants
 *-------------------------------------------------------------*/
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**------------------------------------------------------------
 *  Functions
 *-------------------------------------------------------------*/

/**
 *  \brief Get shared container application instance.
 *
 *  Retorna a instância compartilhada do container da aplicação
 *  ou um serviço registrado com o nome passado por parâmetro. * 
 *  
 *  \param string $service Nome chave do serviço (opcional)
 *  \return FW\Core\Application
 */
function app($service = null)
{
    if ($service) {
        return app()->resolve($service);
    }

    return FW\Core\Application::sharedInstance();
}

/**
 *  \brief An alias for FW\Configuration::get() method
 *  \param string $key
 *  \return mixed.
 */
function config_get($key)
{
    return FW\Configuration::get($key);
}

/**
 *  \brief An alias for FW\Configuration::set() method
 *  \param string $key
 *  \param mixed $val.
 */
function config_set($key, $val)
{
    FW\Configuration::set($key, $val);
}

/**
 *  \brief Global SYSTEM variable wrapper
 *  \param string $key
 *  \return mixed.
 */
function sysconf($key)
{
    return $GLOBALS['SYSTEM'][$key];
}

/**
 *  \brief An alias for FW\Kernel::debug() method
 *  \param string $txt
 *  \param string $name
 *  \param boolean $highlight
 *  \param boolean $revert.
 */
function debug($txt, $name = '', $highlight = true, $revert = true)
{
    FW\Kernel::debug($txt, $name, $highlight, $revert);
}

/**
 *  \brief A var_dump and die help functin
 *  \param mixed $var
 *  \param boolean $die.
 */
function dd($var, $die = true)
{
    echo '<pre>';
    var_dump($var);
    echo '</pre>';

    if ($die) {
        die;
    }
}

/**
 *  \brief Return the object (WTF?).
 */
function with($object)
{
    return $object;
}

/**
 *	\brief Framework autoload function.
 */
function fwgv_autoload($class)
{
    $aclass = explode('\\', $class);
    if (count($aclass) > 1) {
        if ($aclass[0] == 'FW') {
            array_shift($aclass);
        }
        $file = implode(DIRECTORY_SEPARATOR, $aclass);
    } else {
        $file = $aclass[0];
    }

    if (file_exists(sysconf('LIBRARY_PATH').DIRECTORY_SEPARATOR.$file.'.php')) {
        require_once sysconf('LIBRARY_PATH').DIRECTORY_SEPARATOR.$file.'.php';
    } else {
        // procura na user_classes

        // verifica se a classe utiliza o padrão com namespace (ou classe estática)
        // ex: class Oferta_Comercial_Static == arquivo user_classes/oferta/oferta-comercial.static.php

        preg_match('/^(?<class>[A-Za-z]+)(?<subclass>.*?)(?<type>_Static)?$/', $class, $vars);

        $nameSpace = $class = $vars['class'];

        if (!empty($vars['subclass'])) {
            $class .= '-'.substr($vars['subclass'], 1);
        }

        if (isset($vars['type'])) {
            $class .= '.static';
        } else {
            $class .= '.class';
        }

        // procura a classe nas Libarys
        if (file_exists(sysconf('CLASS_PATH').DIRECTORY_SEPARATOR.$nameSpace.DIRECTORY_SEPARATOR.$class.'.php')) {
            require_once sysconf('CLASS_PATH').DIRECTORY_SEPARATOR.$nameSpace.DIRECTORY_SEPARATOR.$class.'.php';
        } elseif (file_exists(sysconf('CLASS_PATH').DIRECTORY_SEPARATOR.$class.'.php')) {
            require_once sysconf('CLASS_PATH').DIRECTORY_SEPARATOR.$class.'.php';
        } else {
            $class = $vars['class'];

            if (!empty($vars['subclass'])) {
                $class .= '_'.substr($vars['subclass'], 1);
            }

            if (isset($vars['type'])) {
                $class .= '.static';
            } else {
                $class .= '.class';
            }

            if (file_exists(sysconf('CLASS_PATH').DIRECTORY_SEPARATOR.$nameSpace.DIRECTORY_SEPARATOR.$class.'.php')) {
                require_once sysconf('CLASS_PATH').DIRECTORY_SEPARATOR.$nameSpace.DIRECTORY_SEPARATOR.$class.'.php';
            } elseif (file_exists(sysconf('CLASS_PATH').DIRECTORY_SEPARATOR.$class.'.php')) {
                require_once sysconf('CLASS_PATH').DIRECTORY_SEPARATOR.$class.'.php';
            }
        }
    }
}

/**
 *	\brief Exception error handler.
 */
function FW_ExceptionHandler($error)
{
    FW\Errors::errorHandler($error->getCode(), $error->getMessage(), $error->getFile(), $error->getLine(), (method_exists($error, 'getContext') ? $error->getContext() : null));
}

/**
 *	\brief Error handler.
 */
function FW_ErrorHandler($errno, $errstr, $errfile, $errline, $errContext)
{
    FW\Errors::errorHandler($errno, $errstr, $errfile, $errline, $errContext);
}

/**
 *  \brief Framework Exception class.
 */
class FW_Exception extends Exception
{
    /// Contexto do erro
    private $context = null;

    /**
     *  \brief Constructor method.
     */
    public function __construct($code, $message, $file, $line, $context = null)
    {
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
        $this->context = $context;
    }

    /**
     *  \brief Return the errot context.
     */
    public function getContext()
    {
        return $this->context;
    }
};
