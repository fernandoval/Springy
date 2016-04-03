<?php
/** \file
 *  Springy.
 *
 *	\brief      Helper file - Functions and constants
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    2.0.4
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
 *  \return Springy\Core\Application
 */
function app($service = null)
{
    if ($service) {
        return app()->resolve($service);
    }

    return Springy\Core\Application::sharedInstance();
}

/**
 *  \brief An alias for Springy\Configuration::get() method
 *  \param string $key
 *  \return mixed.
 */
function config_get($key)
{
    return Springy\Configuration::get($key);
}

/**
 *  \brief An alias for Springy\Configuration::set() method
 *  \param string $key
 *  \param mixed $val.
 */
function config_set($key, $val)
{
    Springy\Configuration::set($key, $val);
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
 *  \brief An alias for Springy\Kernel::debug() method
 *  \param string $txt
 *  \param string $name
 *  \param boolean $highlight
 *  \param boolean $revert.
 */
function debug($txt, $name = '', $highlight = true, $revert = true)
{
    Springy\Kernel::debug($txt, $name, $highlight, $revert);
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
function springyAutoload($class)
{
    $aclass = explode('\\', $class);
    if (count($aclass) > 1) {
        if ($aclass[0] == 'Springy') {
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
function springyExceptionHandler($error)
{
    Springy\Errors::errorHandler($error->getCode(), $error->getMessage(), $error->getFile(), $error->getLine(), (method_exists($error, 'getContext') ? $error->getContext() : null));
}

/**
 *	\brief Error handler.
 */
function springyErrorHandler($errno, $errstr, $errfile, $errline, $errContext)
{
    Springy\Errors::errorHandler($errno, $errstr, $errfile, $errline, $errContext);
}

/**
 *  \brief Framework Exception class.
 */
class SpringyException extends Exception
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
}
