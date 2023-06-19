<?php

/**
 * Dependecy Injection container.
 *
 * @copyright 2015 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 *
 * @version   0.2.0
 */

namespace Springy\Container;

use ArrayAccess;
use Closure;
use InvalidArgumentException;

/**
 * DIContainer class.
 */
class DIContainer implements ArrayAccess
{
    // Type constants
    public const TYPE_FACTORY = 'factory';
    public const TYPE_PARAM = 'param';
    public const TYPE_SHARED = 'shared';

    /** @var array saves keys of all elements in container */
    protected $registeredKeys;

    /** @var array saves all parameters */
    protected $params;
    /** @var array saves all factories */
    protected $factories;
    /** @var array saves all factory extensions */
    protected $factoriesExtensions;
    /** @var arry saves all shared instances */
    protected $sharedInstances;
    /** @var array saves factories that will be shared instances (lazy load) */
    protected $sharedInstancesFactories;

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
     * Registers a parameter.
     *
     * @param string|Closure $key
     * @param mixed          $value any type except object.
     *
     * @return mixed
     */
    public function raw($key, $value = null): mixed
    {
        // If key is a closure returns its result (useful in array mode)
        if ($key instanceof Closure) {
            return call_user_func($key, $this);
        }

        // If value is a closure saves its result in parameter
        if ($value instanceof Closure) {
            $value = call_user_func($value, $this);
        } elseif (is_object($value)) {
            // Deny object
            throw new InvalidArgumentException(
                'The param passed may not be an instance of an object. Use the "instance" method instead.'
            );
        }

        $this->registeredKeys[$key] = self::TYPE_PARAM;
        $this->params[$key] = $value;

        return $value;
    }

    /**
     * Returns the param registered with given key.
     *
     * @param mixed $key
     *
     * @return mixed
     *
     * @throws InvalidArgumentException if parameter $key not exists in container.
     */
    public function param($key): mixed
    {
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }

        throw new InvalidArgumentException("The '{$key}' key was not registered as a param.");
    }

    /**
     * Registers a factory (Closure).
     *
     * Useful to save complex objects creator functions.
     *
     * @param string  $key
     * @param Closure $factory
     *
     * @return void
     */
    public function bind(string $key, Closure $factory): void
    {
        $this->registeredKeys[$key] = self::TYPE_FACTORY;
        $this->factories[$key] = $factory;
    }

    /**
     * Executes a factory and returs its result.
     *
     * @param string $key
     * @param array  $params
     *
     * @return mixed
     *
     * @throws InvalidArgumentException if factory not exists.
     */
    public function make(string $key, array $params = []): mixed
    {
        if (!isset($this->factories[$key])) {
            throw new InvalidArgumentException("The '{$key}' key was not registered as a factory.");
        }

        // Send $params to closure if not empty, else sends this container instance
        $result = empty($params)
            ? call_user_func($this->factories[$key], $this)
            : call_user_func_array($this->factories[$key], $params);

        // If factory has extensions, invokes all
        if (isset($this->factoriesExtensions[$key])) {
            // Calls every extension sending $result and the container instance as parameters
            foreach ($this->factoriesExtensions[$key] as $extension) {
                $result = call_user_func($extension, $result, $this);
            }
        }

        return $result;
    }

    /**
     * Registers an extension into a factory.
     *
     * @param string  $key
     * @param Closure $extension
     *
     * @return void
     *
     * @throws InvalidArgumentException if factory $key not exists.
     */
    public function extend(string $key, Closure $extension): void
    {
        if (!isset($this->factories[$key])) {
            throw new InvalidArgumentException("The '{$key}' key was not registered as a factory.");
        }

        $this->factoriesExtensions[$key][] = $extension;
    }

    /**
     * Registers an instance of a class to be shared.
     *
     * @param string|Closure $key
     * @param Closure|object $instance
     *
     * @return mixed
     *
     * @throws InvalidArgumentException if $instance if not a closure or object.
     */
    public function instance($key, $instance = null): mixed
    {
        // If key is a closure executes it and returns the result (useful in array mode)
        if ($key instanceof Closure) {
            return call_user_func($key, $this);
        }

        $this->registeredKeys[$key] = static::TYPE_SHARED;

        // If $instance is a closure saves it as a lazy load.
        if ($instance instanceof Closure) {
            $this->sharedInstancesFactories[$key] = $instance;

            return null;
        } elseif (!is_object($instance)) {
            throw new InvalidArgumentException('The argument passed is not an instance of an object.');
        }

        $this->sharedInstances[$key] = $instance;

        return $instance;
    }

    /**
     * Returns a shared instance identified by $key.
     *
     * @param string $key
     *
     * @return object
     *
     * @throws InvalidArgumentException if instance not exists.
     */
    public function shared(string $key)
    {
        // If has a lazy load executes it.
        if (isset($this->sharedInstancesFactories[$key])) {
            $this->sharedInstances[$key] = call_user_func($this->sharedInstancesFactories[$key], $this);
            unset($this->sharedInstancesFactories[$key]);
        }

        if (isset($this->sharedInstances[$key])) {
            return $this->sharedInstances[$key];
        }

        throw new InvalidArgumentException("The '{$key}' key was not registered as a shared instance.");
    }

    /**
     * Same as offsetExists (deprecated).
     *
     * @param string $key
     *
     * @return bool
     *
     * @deprecated 4.6
     */
    public function has(string $key): bool
    {
        return $this->offsetExists($key);
    }

    /**
     * Same as offsetGet (deprecated).
     *
     * @param string $key
     *
     * @return mixed
     *
     * @deprecated 4.6
     */
    public function resolve(string $key)
    {
        return $this->offsetGet($key);
    }

    /**
     * Same as offsetUnset (deprecated).
     *
     * @param string $key
     *
     * @return void
     */
    public function forget(string $key): void
    {
        $this->offsetUnset($key);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->registeredKeys[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        if (!isset($this->registeredKeys[$offset])) {
            throw new InvalidArgumentException("The '{$offset}' key was not registered as a dependency.");
        }

        $getter = [
            self::TYPE_FACTORY => fn ($key) => $this->make($key),
            self::TYPE_SHARED => fn ($key) => $this->shared($key),
            self::TYPE_PARAM => fn ($key) => $this->param($key),
        ];

        return call_user_func($getter[$this->registeredKeys[$offset]], $offset);
    }

    public function offsetSet($offset, $value): void
    {
        // Replaces offset if exists.
        if ($this->offsetExists($offset)) {
            $this->offsetUnset($offset);
        }

        // Registers as factory if $value is a closure
        if ($value instanceof Closure) {
            $this->bind($offset, $value);

            return;
        }

        is_object($value)
            ? $this->instance($offset, $value)
            : $this->raw($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        $unset = [
            self::TYPE_FACTORY => function ($key) {
                unset($this->factories[$key]);
                unset($this->factoriesExtensions[$key]);
            },
            self::TYPE_SHARED => function ($key) {
                unset($this->sharedInstances[$key]);
            },
            self::TYPE_PARAM => function ($key) {
                unset($this->params[$key]);
            },
        ];

        if (isset($unset[$this->registeredKeys[$offset] ?? ''])) {
            call_user_func($unset[$this->registeredKeys[$offset]], $offset);
        }

        unset($this->registeredKeys[$offset]);
    }

    /**
     * Returns a new instance of this class.
     *
     * @return self
     */
    public static function newInstance(): self
    {
        return new static();
    }
}
