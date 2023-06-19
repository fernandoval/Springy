<?php

/**
 * Event mediator.
 *
 * @copyright 2015 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 *
 * @version 0.3.0
 */

namespace Springy\Events;

use Closure;
use Springy\Container\DIContainer;

/**
 * Mediator classe.
 */
class Mediator
{
    /** @var DIContainer */
    protected $container;

    /** @var array registered handlers */
    protected $handlers;
    /** @var array handlers masters (wildcards) */
    protected $masterHandlers;
    /** @var array handlers ordered by priority */
    protected $orderedHandlers;
    /** @var string|null event name been fired */
    protected $currentEvent;

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
    }

    /**
     * Sets container.
     *
     * @param DIContainer $container
     *
     * @return void
     */
    public function setContainer(DIContainer $container): void
    {
        $this->container = $container;
    }

    /**
     * Gets the container.
     *
     * @return DIContainer
     */
    public function getContainer(): DIContainer
    {
        return $this->container;
    }

    /**
     * \brief Registra um handler para tratamento de um evento.
     *
     * \param [in] (string|array) $events - nome ou conjunto de nomes que representam o evento
     * \param [in] (variant) $handler - objeto, closure ou nome de depenência que irá tratar o evento
     * \param [in] (int) $priority - prioridade do handler na pilha de execução, maior, mais importante
     */
    /**
     * Register a handler for events.
     *
     * @param array|string   $events
     * @param Closure|string $handler
     * @param int            $priority
     *
     * @return void
     */
    public function registerHandlerFor($events, $handler, int $priority = 0): void
    {
        foreach ((array) $events as $event) { //Para cada nom de evento
            if (strpos($event, '.*') !== false) {//Se houver '*' é um masterHandler
                $this->registerMasterHandler($event, $handler);
                continue; //Registra-o e reoturn
            }

            $this->handlers[$event][$priority][] = $this->resolveHandler($handler); //Registra o handler de acordo com sua prioridade

            unset($this->orderedHandlers[$event]); //Reseta a ordem de prioridade dos handlers
        }
    }

    /**
     * Alias of registerHandlerFor().
     *
     * @deprecated 4.6
     */
    public function on($event, $handler, $priority = 0)
    {
        $this->registerHandlerFor($event, $handler, $priority);
    }

    /**
     * Checks whether event identified by $evet exists.
     *
     * @param string $event
     *
     * @return bool
     */
    public function hasHandlersFor(string $event): bool
    {
        return isset($this->handlers[$event]);
    }

    /**
     * Removes all handlers for the event identified by $event.
     *
     * @param string $event
     *
     * @return void
     */
    public function forget(string $event): void
    {
        unset($this->handlers[$event]);
        unset($this->orderedHandlers[$event]);
    }

    /**
     * Alias of forget().
     *
     * @deprecated 4.6
     */
    public function off($event)
    {
        $this->forget($event);
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
     *
     * @return string|null
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
     * \brief Resolve o tipo do handler
     * \param [in] (variant) $handler - Handler para ser resolvido
     * \return O handler resolvido.
     */
    protected function resolveHandler($handler)
    {
        if (is_string($handler)) { //Se string, nome de dependencia
            return $this->createHandler($handler); //resolver dependencia
        }

        return $handler;
    }

    /**
     * \brief Cria um handler de acordo com o nome do objeto e ação passado por parâmetro,
     *        resolvido pelo container de aplicação (Ex. cache@store)
     * \param [in] (variant) $handler - Handler para ser criado
     * \return \Closure.
     */
    protected function createHandler($handler): Closure
    {
        $container = $this->container;

        return function () use ($handler, $container) {
            $parts = explode('@', $handler);

            $method = count($parts) == 2 ? $parts[1] : 'handle'; //Se não houver ação, o padrão é 'handle'

            $service = [$container[$parts[0]], $method]; //Cria o callable como handler do evento

            return call_user_func_array($service, func_get_args());
        };
    }

    /**
     * \brief Registra um handler 'master' simbolizado por um '*' em sua composição.
     *        Este handler terá prioridade sobre todas os 'sub-handlers'
     * \param [in] (string) $event - Nome do evento
     * \param [in] (variant) $handler - Master Handler.
     */
    protected function registerMasterHandler($event, $handler)
    {
        $this->masterHandlers[$this->getMasterHandlerKey($event)][] = $this->resolveHandler($handler);
    }

    /**
     * \brief Extrai o nome do evento no qual o master handler irá ficar 'escutando'
     * \param [in] (string) $event - Nome do evento
     * \return (string).
     */
    protected function getMasterHandlerKey($event)
    {
        $parts = explode('*', $event);

        return $parts[0];
    }

    /**
     * \brief Retorna os handlers para o evento requisitado
     * \param [in] (string) $event - Nome do evento
     * \return (array).
     */
    protected function getHandlersFor($event)
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
     * \brief Retorna os masters handlers para o evento indicado
     * \param [in] (string) $event - Nome do evento
     * \return (array).
     */
    protected function getMasterHandlersFor($event)
    {
        $masterHandlers = [];

        foreach ($this->masterHandlers as $masterKey => $handlers) {
            if (strpos($event, $masterKey) === 0) { //Se nome do master handler estiver contido no nomedo do evento
                $masterHandlers = array_merge($masterHandlers, $handlers);
            }
        }

        return $masterHandlers;
    }

    /**
     * \brief Ordena os handlers de acordo com suas prioridades
     * \param [in] (string) $event - Nome do evento.
     */
    protected function orderHandlersFor($event)
    {
        $sorted = $this->handlers[$event];

        krsort($sorted, SORT_NUMERIC);

        $this->orderedHandlers[$event] = call_user_func_array('array_merge', $sorted);
    }

    /**
     * \brief Cria e retorna uma nova instancia desta classe
     * \param \Springy\Container\DIContainer $container
     * \return \static.
     */
    public static function newInstance(DIContainer $container = null)
    {
        return new static($container);
    }
}
