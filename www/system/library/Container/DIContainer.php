<?php
/** \file
 *  Springy.
 *
 *  \brief      Classe de container para inversão de controle (Dependecy Injection)
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.1.1
 *  \ingroup    framework
 *
 *  \b Exemplos:
 *
 *  \code
 *  //Configurando
 *    $di = new DIContainer;
 *  $di['db.connectionString'] = 'mysql:host=localhost;dbname=db;charset=utf8';
 *  $di['db.user'] = 'root';
 *  $di['db.password'] = '';
 *  $di['db'] = function($c) {
 *      return new PDO( $c['db.conectionString', $c['db.user'], $c['db.password'] );
 *  };
 *
 * //Utilização - Nova instância
 *  $DB = $di['db'];
 *
 * //Equivalente à
 *  $DB = new PDO('mysql:host=localhost;dbname=db;charset=utf8', 'root', ''); *
 *  \endcode
 */

namespace Springy\Container;

use ArrayAccess;
use Closure;
use InvalidArgumentException;

/**
 * \brief Classe de container para inversão de controle (Dependecy Injection).
 */
class DIContainer implements ArrayAccess
{
    /// Constante que indica o tipo do elemento como uma factory
    const TYPE_FACTORY = 'factory';
    /// Constante que indica o tipo do elemento como um parâmetro
    const TYPE_PARAM = 'param';
    /// Constante que indica o tipo do elemento como uma instância compartilhada
    const TYPE_SHARED = 'shared';

    /// Array que armazena as chaves de todos os elemntos registrados no container
    protected $registeredKeys;

    /// Array que armazena os parâmetros registrados no container
    protected $params;
    /// Array que armazena as factories registradas no container
    protected $factories;
    /// Array que armazena as extensões de factories registradas no container
    protected $factoriesExtensions;
    /// Array que armazena as instâncias compartilhadas registradas no container
    protected $sharedInstances;
    /// Array que armazena os factories que se tornarão instâncias  compartilhadas quando chamadas (lazy load)
    protected $sharedInstancesFactories;

    /**
     * \brief Inicializa a classe e seus atributos.
     */
    public function __construct()
    {
        $this->registeredKeys = [];
        $this->params = [];
        $this->factories = [];
        $this->factoriesExtensions = [];
        $this->sharedInstances = [];
        $this->sharedInstancesFactories = [];
    }

    /**
     * \brief Registra um parâmetro de tipo simples no container. O parametro pode ser qualquer numerico, booleanos,
     *        strings ou arrays.
     *
     * \param {in} (string|\Closure) $key - chave identificadora do parâmetro registrado ou uma função de tratamento.
     * \param [in] (variant) $value - (Optional) Parâmetro ou função de tratamento retornando o parâmetro.
     * \return Retorna o parâmetro registrado.
     * \throws \InvalidArgumentException
     */
    public function raw($key, $value = null)
    {
        //Se a chave for uma closure então retornar resultado dessa closure (útil para utilizar no modo array)
        if ($key instanceof Closure) {
            return call_user_func($key, $this);
        }

        //Se valor for uma closure, então o parâmetro é o retorno dessa closure.
        if ($value instanceof Closure) {
            $value = call_user_func($value, $this);
        } elseif (is_object($value)) { //Objetos não são permitidos, exceção disparada
            throw new InvalidArgumentException("The param passed may not be an instance of an object. Use the 'instance' method instead.");
        }

        $this->registeredKeys[$key] = static::TYPE_PARAM;
        $this->params[$key] = $value;

        return $value;
    }

    /**
     * \brief Retorna um pârametro registrado com esta chave identificadora, se existir.
     *
     * \param {in} (string) $key - chave identificadora do parâmetro registrado.
     * \return Retorna o parâmetro registrado.
     * \throws \InvalidArgumentException
     */
    public function param($key)
    {
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }

        throw new InvalidArgumentException("The '{$key}' key was not registered as a param.");
    }

    /**
     * \brief Registra um serviço factory (Closures) no container, útil para guardar rotinas
     *        de criação de objetos complexos.
     *
     * \param {in} (string) $key - chave identificadora do serviço factory registrado.
     * \param [in] (\Closure) $factory - Closure que será usada para execução de um serviço.
     */
    public function bind($key, Closure $factory)
    {
        $this->registeredKeys[$key] = static::TYPE_FACTORY;
        $this->factories[$key] = $factory;
    }

    /**
     * \brief Executa um serviço e retorna o resultado desta factory e suas extensões.
     *
     * \param {in} (string) $key - chave identificadora da factory registrada no container.
     * \param [in] (array) $params - (Optional) Parâmetros que serão passados para a factory na sua execução.
     * \return Retorna o resultado gerado pela factory e suas extensões.
     * \throws \InvalidArgumentException
     */
    public function make($key, array $params = [])
    {
        if (!isset($this->factories[$key])) {
            throw new InvalidArgumentException("The '{$key}' key was not registered as a factory.");
        }

        if (!empty($params)) {//Se houver parâmetros passá-los para a closure do serviço
            $result = call_user_func_array($this->factories[$key], $params);
        } else {//Caso contrário, passar esta instância de container como parâmetro para a closure
            $result = call_user_func($this->factories[$key], $this);
        }

        //Se houver extensões registradas para esta chave identificadora
        if (isset($this->factoriesExtensions[$key])) {
            foreach ($this->factoriesExtensions[$key] as $extension) {//Executar todas passando o próprio resultado e esta instância de container como parâmeetro
                $result = call_user_func($extension, $result, $this);
            }
        }

        return $result;
    }

    /**
     * \brief Registra uma extênsão de serviço no container.
     *
     * \param {in} (string) $key - chave identificadora do serviço já registrado no container.
     * \param [in] (\Closure) $extension - Closure de extensão que será executada toda vez que um serviço for chamado.
     * \throws \InvalidArgumentException
     */
    public function extend($key, Closure $extension)
    {
        if (!isset($this->factories[$key])) {
            throw new InvalidArgumentException("The '{$key}' key was not registered as a factory.");
        }

        $this->factoriesExtensions[$key][] = $extension;
    }

    /**
     * \brief Registra uma instância de classe no container para ser compartilhada pelos consumidores do container.
     *
     * \param {in} (string|\Closure) $key - chave identificadora da instância registrada ou uma função de tratamento.
     * \param [in] (variant) $value - (Optional) Instância ou função de tratamento retornando a instância.
     * \return Retorna a instância registrada.
     * \throws \InvalidArgumentException
     */
    public function instance($key, $instance = null)
    {
        //Se chave for uma closure, esta é executada e seu resultado é retornado (útil para ser usado com o modo array do container)
        if ($key instanceof Closure) {
            return call_user_func($key, $this);
        }

        $this->registeredKeys[$key] = static::TYPE_SHARED;

        if ($instance instanceof Closure) { //Se instância for uma closure, então a instância a ser registrada será o resutado dessa closure.
            $this->sharedInstancesFactories[$key] = $instance;

            return;
        } elseif (!is_object($instance)) { //Somente instâncias de classes são permitidas, exceção é disparada
            throw new InvalidArgumentException('The argument passed is not an instance of an object.');
        }

        $this->sharedInstances[$key] = $instance;

        return $instance;
    }

    /**
     * \brief Retorna uma instância compartilhada registrada com esta chave identificadora, se existir.
     *
     * \param {in} (string) $key - chave identificadora da instância compartilhada registrada.
     * \return Retorna a instância registrada.
     * \throws \InvalidArgumentException
     */
    public function shared($key)
    {
        if (isset($this->sharedInstancesFactories[$key])) { //Lazy loading as instancias compartilhadas.
            $this->sharedInstances[$key] = call_user_func($this->sharedInstancesFactories[$key], $this);

            unset($this->sharedInstancesFactories[$key]);
        }

        if (isset($this->sharedInstances[$key])) {
            return $this->sharedInstances[$key];
        }

        throw new InvalidArgumentException("The '{$key}' key was not registered as a shared instance.");
    }

    /**
     * \brief Retorna se o container tem determinada chava registrada.
     *
     * \param {in} (string) $key - chave identificadora.
     * \return true para sim, false para não.
     */
    public function has($key)
    {
        return $this->offsetExists($key);
    }

    /**
     *  \brief Retorna a dependencia independente do seu tipo.
     *
     *  \param [in] (string) $key - Chave identificadora da dependencia
     *  \return Dependencia equivalente a chave
     *  \throws InvalidArgumentException
     */
    public function resolve($key)
    {
        if (!isset($this->registeredKeys[$key])) {
            throw new InvalidArgumentException("The '{$key}' key was not registered as a dependency.");
        }

        switch ($this->registeredKeys[$key]) {
            case static::TYPE_FACTORY:
                return $this->make($key);

            case static::TYPE_SHARED:
                return $this->shared($key);

            case static::TYPE_PARAM:
            default:
                return $this->param($key);
        }
    }

    /**
     * \brief Remove um elemento registrado no container.
     *
     * \param {in} (string) $key - chave identificadora do elemento registrado.
     */
    public function forget($key)
    {
        unset($this[$key]);
    }

    /**
     * \brief (PHP 5 &gt;= 5.0.0) Whether a offset exists
     * \link http://php.net/manual/en/arrayaccess.offsetexists.php
     * \param [in] (variant) $offset - An offset to check for.
     * \return boolean true on success or false on failure.
     *         The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->registeredKeys[$offset]);
    }

    /**
     * \brief (PHP 5 &gt;= 5.0.0) Offset to retrieve
     * \link http://php.net/manual/en/arrayaccess.offsetget.php
     * \param [in] (variant) $offset - The offset to retrieve.
     * \return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->resolve($offset);
    }

    /**
     * \brief (PHP 5 &gt;= 5.0.0) Offset to set
     * \link http://php.net/manual/en/arrayaccess.offsetset.php
     * \param [in] (variant) $offset - The offset to assign the value to.
     * \param [in] (variant) $value - The value to set.
     */
    public function offsetSet($offset, $value)
    {
        if ($this->has($offset)) { //Se elemento já existe, é substituido.
            $this->forget($offset);
        }

        if ($value instanceof Closure) { //Se for uma closure, faz o bind
            $this->bind($offset, $value);

            return;
        }

        if (is_object($value)) { //Se é uma instância
            $this->instance($offset, $value);

            return;
        }

        //Parâmetro
        $this->raw($offset, $value);
    }

    /**
     * \brief (PHP 5 &gt;= 5.0.0) Offset to unset
     * \link http://php.net/manual/en/arrayaccess.offsetunset.php
     * \param [in] (variant) $offset - The offset to unset.
     */
    public function offsetUnset($offset)
    {
        switch ($this->registeredKeys[$offset]) {
            case static::TYPE_FACTORY:
                unset($this->factories[$offset]);
                unset($this->factoriesExtensions[$offset]);
                break;
            case static::TYPE_SHARED:
                unset($this->sharedInstances[$offset]);
                break;
            case static::TYPE_PARAM:
                unset($this->params[$offset]);
        }

        unset($this->registeredKeys[$offset]);
    }

    /**
     * \brief Cria e retorna uma nova instância de classe
     * \return DIContainer.
     */
    public static function newInstance()
    {
        return new static();
    }
}
