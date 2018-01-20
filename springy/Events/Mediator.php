<?php
/** \file
 *  Springy.
 *
 *  \brief      Classe de intermediadora de administração de eventos.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.2.2
 *  \ingroup    framework
 */

namespace Springy\Events;

use Springy\Container\DIContainer;

/**
 * \brief Classe de intermediadora de administração de eventos.
 */
class Mediator
{
    ///Container para injeção de dependência de objetos
    protected $container;

    ///Array que armazena os handlers registrados
    protected $handlers;
    ///Array que armazena os handlers masters (wildcards)
    protected $masterHandlers;
    ///Array que armazena todos os handlers ordenados por ordem de prioridade
    protected $orderedHandlers;
    ///Armazena o valor do evento atualmente sendo disparado
    protected $currentEvent;

    /**
     * \brief Construtor da classe
     * \param {in} (DIContainer) $container - container que fará o gerenciamento das dependências.
     */
    public function __construct(DIContainer $container = null)
    {
        $this->container = $container ?: new DIContainer();
        $this->handlers = [];
        $this->masterHandlers = [];
        $this->orderedHandlers = [];
    }

    public function setContainer(DIContainer $container)
    {
        $this->container = $container;
    }

    public function getContainer()
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
    public function registerHandlerFor($events, $handler, $priority = 0)
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
     * \brief Alias of registerHandlerFor().
     *
     * \see registerHandlerFor()
     */
    public function on($event, $handler, $priority = 0)
    {
        $this->registerHandlerFor($event, $handler, $priority);
    }

    /**
     * \brief Se existe handlers para este evento.
     *
     * \param [in] (string) $event - Nome identificador do evento
     * \return true se sim, false se não
     */
    public function hasHandlersFor($event)
    {
        return isset($this->handlers[$event]);
    }

    /**
     * \brief Remove todos os handlers de um evento.
     *
     * \param [in] (string) $event - Nome identificador do evento
     */
    public function forget($event)
    {
        unset($this->handlers[$event]);
        unset($this->orderedHandlers[$event]);
    }

    /**
     * \brief Alias of forget().
     *
     * \see forget()
     */
    public function off($event)
    {
        $this->forget($event);
    }

    /**
     * \brief Dispara um evento.
     *
     * \param [in] string $event - Nome identificador do evento
     * \param [in] array $data - Dados à serem passados para os handlers
     * \return Resultados de todos os handlers disparados
     */
    public function fire($event, $data = [])
    {
        //Transforma dados em array se não for (para trabalhar com call_user_func_array() facilmente
        if (!is_array($data)) {
            $data = [$data];
        }

        if ($this->hasHandlersFor($event)) {
            $responses = [];

            $this->currentEvent = $event;

            foreach ($this->getHandlersFor($event) as $handler) {
                $res = call_user_func_array($handler, $data);

                //Se retorno da execução do handler for exatamente igual a falso, interrompe a corrente de handlers
                if ($res === false) {
                    break;
                }

                $responses[] = $res;
            }

            $this->currentEvent = null;

            return $responses;
        }
    }

    /**
     * \brief O evento sendo disparado no momento.
     *
     * \return nome do evento sendo disparado
     */
    public function current()
    {
        return $this->currentEvent;
    }

    /**
     * \brief Registra uma classe handler como subscriber     *
     * \param [in] (variant) $param - O objeto handler que irá subscrever à eventos.
     */
    public function subscribe($handler)
    {
        if (is_string($handler)) { //Se string, nome de dependencia
            $handler = $this->container[$handler]; //resolver dependencia
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
    protected function createHandler($handler)
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
