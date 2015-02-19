<?php
/** \file
 *  FVAL PHP Framework for Web Applications
 *  
 *  \copyright Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright Copyright (c) 2007-2015 Fernando Val\n
 *
 *	\brief rquivo de helpers - Funções e Constantes 
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version 1.0.0
 *  \author Fernando Val - fernando.val@gmail.com
 *  \author Allan Marques - allan.marques@ymail.com
 *  \ingroup framework
 */

/*
 * para deixar o desenvolvedor mais feliz e produtivo 
 */

/**------------------------------------------------------------
 * Constantes
 *-------------------------------------------------------------*/
if ( !defined('DS') ) {
    define('DS', DIRECTORY_SEPARATOR);
}


/**------------------------------------------------------------
 * Funções
 *-------------------------------------------------------------*/

/**
 * Retorna a instância compartilhada do container da aplicação
 * ou um serviço registrado com o nome passado por parâmetro. * 
 * @param string $service Nome chave do serviço (opcional)
 * @return FW\Core\Application
 */
function app($service = null)
{
    if ( $service ) {
        return app()->resolve($service);
    }
    
    return FW\Core\Application::sharedInstance();
}

/**
 * Alias de FW\Configuration::get()
 * @param string $key
 * @return mixed
 */
function config_get($key)
{
    return FW\Configuration::get($key);
}

/**
 * Alias de FW\Configuration::set()
 * @param string $key
 * @param mixed $val
 */
function config_set($key, $val)
{
    FW\Configuration::set($key, $val);
}

/**
 * Wrapper da variável global $SYSTEM
 * @param string $key
 * @return mixed
 */
function sysconf($key)
{
    return $GLOBALS['SYSTEM'][$key];
}

/**
 * Alias de FW\Kernel::debug()
 * @param string $txt
 * @param string $name
 * @param boolean $highlight
 * @param boolean $revert
 */
function debug($txt, $name='', $highlight=true, $revert=true)
{
    FW\Kernel::debug($txt, $name, $highlight, $revert);
}

/**
 * var_dump e die uma variável
 * @param mixed $var
 * @param boolean $die
 */
function dd($var, $die = true)
{
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    
    if ($die) die;
}

function with($object)
{
    return $object;
}