<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *	\copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.
 *	\copyright Copyright (c) 2007-2014 Fernando Val
 *	\copyright Copyright (c) 2014 Allan Marques
 *
 *	\brief		Interface para classes handlers de eventos
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	0.1
 *  \author		Allan Marques - allan.marques@ymail.com
 *	\ingroup	framework
 */
namespace FW\Events;

/**
 * \brief Interface para classes handlers de eventos
 */
interface HandlerInterface
{
    /**
     * \brief Registra esta classe como handlers nos evetnos necessários.
     */
    public function subscribes(Mediator $mediator);
} 