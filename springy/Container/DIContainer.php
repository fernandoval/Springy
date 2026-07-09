<?php

/**
 * Dependecy Injection container.
 *
 * @copyright 2015 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 */

namespace Springy\Container;

use ArrayAccess;
use Closure;
use InvalidArgumentException;

class DIContainer implements ArrayAccess
{
    // Type constants
    public const TYPE_FACTORY = 'factory';
    public const TYPE_PARAM = 'param';
    public const TYPE_SHARED = 'shared';

    // Keys of all elements in container
    protected array $registeredKeys;

    protected array $params;
    protected array $factories;
    protected array $factoriesExtensions;
    protected array $sharedInstances;
    protected array $sharedInstancesFactories; // Factories that will be shared instances (lazy load)

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
     * The value can be any type except object.
     *
     * @throws InvalidArgumentException if value is an object.
     */
    public function raw(string|Closure $key, mixed $value = null): mixed
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
     * @throws InvalidArgumentException if parameter $key not exists in container.
     */
    public function param(string $key): mixed
    {
        return $this->params[$key] ?? throw new InvalidArgumentException(
            "The '{$key}' key was not registered as a param."
        );
    }

    /**
     * Registers a factory (Closure).
     *
     * Useful to save complex objects creator functions.
     */
    public function bind(string $key, Closure $factory): void
    {
        $this->registeredKeys[$key] = self::TYPE_FACTORY;
        $this->factories[$key] = $factory;
    }

    /**
     * Executes a factory and returs its result.
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
     * @throws InvalidArgumentException if $instance if not a closure or object.
     */
    public function instance(string|Closure $key, ?object $instance = null): mixed
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
        }

        $this->sharedInstances[$key] = $instance;

        return $instance;
    }

    /**
     * Returns a shared instance identified by $key.
     *
     * @throws InvalidArgumentException if instance not exists.
     *
     * @return object
     */
    public function shared(string $key): object
    {
        // If has a lazy load executes it.
        if (isset($this->sharedInstancesFactories[$key])) {
            $this->instance($key, call_user_func($this->sharedInstancesFactories[$key], $this));
            unset($this->sharedInstancesFactories[$key]);
        }

        return $this->sharedInstances[$key] ?? throw new InvalidArgumentException(
            "The '{$key}' key was not registered as a shared instance."
        );
    }

    /**
     * Same as offsetUnset (deprecated).
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

        return call_user_func(
            match ($this->registeredKeys[$offset]) {
                self::TYPE_FACTORY => fn ($key) => $this->make($key),
                self::TYPE_SHARED => fn ($key) => $this->shared($key),
                self::TYPE_PARAM => fn ($key) => $this->param($key),
            },
            $offset
        );
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
        call_user_func(
            match ($this->registeredKeys[$offset] ?? '') {
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
                default => fn () => null,
            },
            $offset
        );

        unset($this->registeredKeys[$offset]);
    }

    /**
     * Returns a new instance of this class.
     */
    public static function newInstance(): self
    {
        return new static();
    }
}
