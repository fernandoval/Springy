<?php

/**
 * Application dependency container.
 *
 * @copyright 2015 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 *
 * @version 0.3.0
 */

namespace Springy\Core;

use Springy\Container\DIContainer;
use Springy\Events\Mediator;

/**
 * Application class.
 */
class Application extends DIContainer
{
    /** @var self static instance of this class */
    protected static $sharedInstance;

    public function __construct()
    {
        parent::__construct();

        $this->bindDefaultDependencies();
    }

    /**
     * Register an event handler.
     *
     * @param string|array $event
     * @param mixed        $handler
     * @param int          $priority
     *
     * @return self
     */
    public function on($event, $handler, int $priority = 0): self
    {
        $this->offsetGet('events')->registerHandlerFor($event, $handler, $priority);

        return $this;
    }

    /**
     * Removes an event handler.
     *
     * @param string $event
     *
     * @return self
     */
    public function off(string $event): self
    {
        $this->offsetGet('events')->forget($event);

        return $this;
    }

    /**
     * Fires the event $event with parameters $data.
     *
     * @param string $event
     * @param array  $data
     *
     * @return mixed
     */
    public function fire(string $event, array $data = []): mixed
    {
        return $this->offsetGet('events')->fire($event, $data);
    }

    /**
     * Returns the shared instance of the class.
     *
     * @return self
     */
    public static function sharedInstance(): self
    {
        if (!static::$sharedInstance) {
            static::$sharedInstance = new static();
        }

        return static::$sharedInstance;
    }

    /**
     * Registers default dependencies for the application.
     *
     * @return void
     */
    protected function bindDefaultDependencies(): void
    {
        $this->instance('events', new Mediator($this));
    }
}
