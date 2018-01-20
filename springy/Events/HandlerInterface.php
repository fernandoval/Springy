<?php
/** \file
 *  Springy.
 *
 *  \brief      Interface para classes handlers de eventos.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.1.1
 *  \ingroup    framework
 */

namespace Springy\Events;

/**
 * \brief Interface para classes handlers de eventos.
 */
interface HandlerInterface
{
    /**
     * \brief Registra esta classe como handlers nos evetnos necessários.
     */
    public function subscribes(Mediator $mediator);
}
