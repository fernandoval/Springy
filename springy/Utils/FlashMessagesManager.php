<?php
/** \file
 *  Springy.
 *
 *  \brief      Classe que gerenciar dados flash de sessão, ou seja, dados que ficam disponíveis por somente um request.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.1.1
 *  \ingroup    framework
 */

namespace Springy\Utils;

use Springy\Session;

/**
 * \brief Classe que gerenciar dados flash de sessão, ou seja, dados que ficam disponíveis por somente um request.
 */
class FlashMessagesManager
{
    /// Mensagens de erros guardados no ultimo request
    protected $oldErrors;
    /// Mensagens genéricas guardadas no último request
    protected $oldMessages;
    /// Mensagens de erros que serão guardadas para o próximo request
    protected $newErrors;
    /// Mensagens genéricas que serão guardadas para o próximo request
    protected $newMessages;
    /// Nome identificador das mensagens na sessão
    protected $flashKey = '__FLASHDATA__';

    /**
     *  \brief Construtor da classe.
     */
    public function __construct()
    {
        $this->oldErrors = new MessageContainer();
        $this->oldMessages = new MessageContainer();
        $this->newErrors = new MessageContainer();
        $this->newMessages = new MessageContainer();

        $this->loadLastSessionData();
        // Remove os dados da sessão guardados no último request
        Session::unregister($this->flashKey);
    }

    /**
     *  \brief Seta o container de mensagens de erros que serão guardados para o próximo request.
     *  \param [in] (\Springy\Utils\MessageContainer) $errors - Container de mensagem de erros.
     */
    public function setErrors(MessageContainer $errors)
    {
        $this->newErrors = $errors;
    }

    /**
     *  \brief Retorna o container de mensagens de erros que serão guardados para o próximo request.
     *  \return (\Springy\Utils\MessageContainer).
     */
    public function errors()
    {
        return $this->newErrors;
    }

    /**
     *  \brief Seta o container de mensagens genéricas que serão guardadas para o próximo request.
     *  \param [in] (\Springy\Utils\MessageContainer) $errors - Container de mensagem de erros.
     */
    public function setMessages(MessageContainer $messages)
    {
        $this->newMessages = $messages;
    }

    /**
     *  \brief Retorna o container de mensagens genéricas que serão guardadas para o próximo request.
     *  \return (\Springy\Utils\MessageContainer).
     */
    public function messages()
    {
        return $this->newMessages;
    }

    /**
     *  \brief Retorna o container de mensagens de erros que foram guardados no último request.
     *  \return (\Springy\Utils\MessageContainer).
     */
    public function lastErrors()
    {
        return $this->oldErrors;
    }

    /**
     *  \brief Retorna o container de mensagens genéricas que foram guardadas no último request.
     *  \return (\Springy\Utils\MessageContainer).
     */
    public function lastMessages()
    {
        return $this->oldMessages;
    }

    /**
     *  \brief Carrega os containers de mensagens que foram guardanos no último request, se existirem.
     */
    protected function loadLastSessionData()
    {
        $sessionData = Session::get($this->flashKey);

        if (isset($sessionData['errors'])) {
            $this->oldErrors->setMessages($sessionData['errors']);
        }

        if (isset($sessionData['messages'])) {
            $this->oldMessages->setMessages($sessionData['messages']);
        }
    }

    /**
     *  \brief Guarda as mensagens para serem carregadas no próximo request.
     */
    protected function registerSessionData()
    {
        $flashData = [];

        if ($this->newErrors->hasAny()) {
            $flashData['errors'] = $this->newErrors->getMessages();
        }

        if ($this->newMessages->hasAny()) {
            $flashData['messages'] = $this->newMessages->getMessages();
        }

        if (!empty($flashData)) {
            Session::set($this->flashKey, $flashData);
        }
    }

    /**
     *  \brief Destrutor da classe.
     */
    public function __destruct()
    {
        $this->registerSessionData();
    }
}
