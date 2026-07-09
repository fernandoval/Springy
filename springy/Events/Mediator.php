<?php

/**
 * Event mediator.
 *
 * @copyright 2015 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 */

namespace Springy\Events;

use Closure;
use Springy\Container\DIContainer;

class Mediator
{
    protected DIContainer $container;

    /** @var array registered handlers */
    protected array $handlers;
    /** @var array handlers masters (wildcards) */
    protected array $masterHandlers;
    /** @var array handlers ordered by priority */
    protected array $orderedHandlers;
    /** @var string|null event name been fired */
    protected string|null $currentEvent;

    /**
     * Constructor.
     *
     * @param DIContainer|null $container
     */
    public function __construct(?DIContainer $container = null)
    {
        $this->container = $container ?? new DIContainer();
        $this->handlers = [];
        $this->masterHandlers = [];
        $this->orderedHandlers = [];
        $this->currentEvent = null;
    }

    /**
     * Sets the container.
     */
    public function setContainer(DIContainer $container): void
    {
        $this->container = $container;
    }

    /**
     * Gets the container.
     */
    public function getContainer(): DIContainer
    {
        return $this->container;
    }

    /**
     * Register a handler for events.
     *
     * @param array|string   $events   nome ou conjunto de nomes que representam o evento.
     * @param Closure|string $handler  objeto, closure ou nome de depenência que irá tratar o evento.
     * @param int            $priority prioridade do handler na pilha de execução, maior, mais importante.
     */
    public function registerHandlerFor(array|string $events, Closure|string $handler, int $priority = 0): void
    {
        // Para cada nom de evento
        foreach ((array) $events as $event) {
            // Se houver '*' é um masterHandler
            if (strpos($event, '.*') !== false) {
                $this->registerMasterHandler($event, $handler);

                continue; // Registra-o e return
            }

            // Registra o handler de acordo com sua prioridade
            $this->handlers[$event][$priority][] = $this->resolveHandler($handler);

            // Reseta a ordem de prioridade dos handlers
            unset($this->orderedHandlers[$event]);
        }
    }

    /**
     * Checks whether event identified by $evet exists.
     */
    public function hasHandlersFor(string $event): bool
    {
        return isset($this->handlers[$event]);
    }

    /**
     * Removes all handlers for the event identified by $event.
     */
    public function forget(string $event): void
    {
        unset($this->handlers[$event]);
        unset($this->orderedHandlers[$event]);
    }

    /**
     * Fires the event identified by $event.
     *
     * @param string $event
     * @param array  $data
     *
     * @return array with result for all event handlers.
     */
    public function fire(string $event, array $data = []): array
    {
        if (!$this->hasHandlersFor($event)) {
            return [];
        }

        $responses = [];
        $this->currentEvent = $event;

        foreach ($this->getHandlersFor($event) as $handler) {
            $res = call_user_func_array($handler, $data);

            if ($res === false) {
                // Stop the process if the handler returns false.
                break;
            }

            $responses[] = $res;
        }

        $this->currentEvent = null;

        return $responses;
    }

    /**
     * Returns the event been fired.
     */
    public function current(): ?string
    {
        return $this->currentEvent;
    }

    /**
     * Register a handler class as subscriber.
     *
     * @param object $handler
     *
     * @return void
     */
    public function subscribe($handler): void
    {
        if (is_string($handler)) { // Se string, nome de dependencia
            $handler = $this->container[$handler]; // resolver dependencia
        }

        $handler->subscribes($this);
    }

    /**
     * Resolve o tipo do handler.
     *
     * @param Closure|string $handler Handler para ser resolvido
     *
     * @return Closure O handler resolvido.
     */
    protected function resolveHandler(Closure|string $handler): Closure
    {
        return is_string($handler) ? $this->createHandler($handler) : $handler;
    }

    /**
     * Cria um handler de acordo com o nome do objeto e ação passado por parâmetro,
     * resolvido pelo container de aplicação (Ex. cache@store).
     *
     * @param string $handler Handler para ser criado
     */
    protected function createHandler(string $handler): Closure
    {
        $container = $this->container;

        return function () use ($handler, $container) {
            $parts = explode('@', $handler);
            $method = count($parts) == 2 ? $parts[1] : 'handle'; // Se não houver ação, o padrão é 'handle'
            $service = [$container[$parts[0]], $method]; // Cria o callable como handler do evento

            return call_user_func_array($service, func_get_args());
        };
    }

    /**
     * Registra um handler 'master' simbolizado por um '*' em sua composição.
     *
     * Este handler terá prioridade sobre todas os 'sub-handlers'.
     *
     * @param string $event   Nome do evento
     * @param mixed  $handler Master Handler.
     */
    protected function registerMasterHandler(string $event, mixed $handler): void
    {
        $this->masterHandlers[$this->getMasterHandlerKey($event)][] = $this->resolveHandler($handler);
    }

    /**
     * Extrai o nome do evento no qual o master handler irá ficar 'escutando'.
     */
    protected function getMasterHandlerKey(string $event): string
    {
        $parts = explode('*', $event);

        return $parts[0];
    }

    /**
     * Retorna os handlers para o evento requisitado.
     */
    protected function getHandlersFor(string $event): array
    {
        if (!isset($this->orderedHandlers[$event])) {
            $this->orderHandlersFor($event);
        }

        return array_merge(
            $this->orderedHandlers[$event],
            $this->getMasterHandlersFor($event)
        );
    }

    /**
     * Retorna os masters handlers para o evento indicado.
     */
    protected function getMasterHandlersFor(string $event): array
    {
        $masterHandlers = [];

        foreach ($this->masterHandlers as $masterKey => $handlers) {
            // Se nome do master handler estiver contido no nome do do evento
            if (strpos($event, $masterKey) === 0) {
                $masterHandlers = array_merge($masterHandlers, $handlers);
            }
        }

        return $masterHandlers;
    }

    /**
     * Ordena os handlers de acordo com suas prioridades.
     */
    protected function orderHandlersFor(string $event): void
    {
        $sorted = $this->handlers[$event];

        krsort($sorted, SORT_NUMERIC);

        $this->orderedHandlers[$event] = call_user_func_array('array_merge', $sorted);
    }

    /**
     * Cria e retorna uma nova instancia desta classe.
     */
    public static function newInstance(?DIContainer $container = null): self
    {
        return new static($container);
    }
}
