<?php

/**
 * Session flash data manager.
 *
 * @copyright 2015 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version 0.2.0
 */

namespace Springy\Utils;

use Springy\Session;

class FlashMessagesManager
{
    /** @var MessageContainer last request error messages */
    protected $oldErrors;
    /** @var MessageContainer last request generic messages */
    protected $oldMessages;
    /** @var MessageContainer error messages saved to next request */
    protected $newErrors;
    /** @var MessageContainer generic messages saved to next request */
    protected $newMessages;
    /** @var string flash data identificator */
    protected $flashKey = '__FLASHDATA__';

    public function __construct()
    {
        $this->oldErrors = new MessageContainer();
        $this->oldMessages = new MessageContainer();
        $this->newErrors = new MessageContainer();
        $this->newMessages = new MessageContainer();

        $this->loadLastSessionData();
        // Clear previous request data
        Session::unregister($this->flashKey);
    }

    /**
     * Sets an error message container to the next request.
     *
     * @param MessageContainer $errors
     *
     * @return void
     */
    public function setErrors(MessageContainer $errors)
    {
        $this->newErrors = $errors;
    }

    /**
     * Gets the error message container saved to next request.
     *
     * @return MessageContainer
     */
    public function errors()
    {
        return $this->newErrors;
    }

    /**
     * Sets a generic message container to the next request.
     *
     * @param MessageContainer $messages
     *
     * @return void
     */
    public function setMessages(MessageContainer $messages)
    {
        $this->newMessages = $messages;
    }

    /**
     * Gets the generic message container saved to the next request.
     *
     * @return MessageContainer
     */
    public function messages()
    {
        return $this->newMessages;
    }

    /**
     * Gets the error message container saved at the previous request.
     *
     * @return MessageContainer
     */
    public function lastErrors()
    {
        return $this->oldErrors;
    }

    /**
     * Gets the generic messages container saved at the previous request.
     *
     * @return MessageContainer
     */
    public function lastMessages()
    {
        return $this->oldMessages;
    }

    /**
     * Loads the message container saved at the last request.
     *
     * @return void
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
     * Saves the messages into session to be loaded at the next request.
     *
     * @return void
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

    public function __destruct()
    {
        $this->registerSessionData();
    }
}
