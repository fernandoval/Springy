<?php
/**	\file
 *	FVAL PHP Framework for Web Applications.
 *
 *  \copyright	Copyright (c) 2007-2016 FVAL Consultoria e Informática Ltda.\n
 *  \copyright	Copyright (c) 2007-2016 Fernando Val\n
 *	\copyright Copyright (c) 2014 Allan Marques
 *
 *	\brief		Classe container de mensagens de texto
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	0.1
 *  \author		Allan Marques - allan.marques@ymail.com
 *	\ingroup	framework
 */
namespace FW\Utils;

use ArrayAccess;

/**
 *	\brief		Classe container de mensagens de texto.
 */
class MessageContainer implements ArrayAccess
{
    /// Array que guarda as mensagens adicionadas no container
    protected $messages;
    /// Formato do placeholder da mensagem
    protected $format = ':msg';

    /**
     * \brief Construtor da classe
     * \param [in] (array) $messages - Mensagens iniciais.
     */
    public function __construct(array $messages = [])
    {
        $this->messages = [];
        $this->setMessages($messages);
    }

    /**
     * \brief Há alguma mensagem armazenada no container?
     * \return (bool).
     */
    public function hasAny()
    {
        return !empty($this->messages);
    }

    /**
     * \brief Há alguma mensagem armazenada com essa chave de identificação?
     * \param [in] (string) $key
     * \return (bool).
     */
    public function has($key)
    {
        return isset($this->messages[$key]) && $this->messages[$key] != '';
    }

    /**
     * \brief Retorna a primeira mensagem armazenada com essa chave de identificação
     * \param [in] (string) $key - Chave identificadora
     * \param [in] (string) $format - Formato do placeholder
     * \return (variant).
     */
    public function first($key, $format = ':msg')
    {
        if ($this->has($key)) {
            $first = $this->formatMsg($key, reset($this->messages[$key]), $format);

            return $first[0];
        }

        return;
    }

    /**
     * \brief Retorna todas as mensagens armazenadas com essa chave de identificação
     * \param [in] (string) $key - Chave identificadora
     * \param [in] (string) $format - Formato do placeholder
     * \return (array).
     */
    public function get($key, $format = ':msg')
    {
        if ($this->has($key)) {
            return $this->formatMsg($key, $this->messages[$key], $format);
        }

        return [];
    }

    /**
     * \brief Retorna todas as mensagens armazenadas
     * \param [in] (string) $format - Formato do placeholder
     * \return array.
     */
    public function all($format = ':msg')
    {
        $msgs = [];

        foreach ($this->messages as $key => $msg) {
            $msgs = array_merge($msgs, $this->formatMsg($key, $msg, $format));
        }

        return $msgs;
    }

    /**
     * \brief Adiciona uma mensagem relacionada à chave identificadora
     * \param [in] (string) $key - Chave identificadora
     * \param [in] (string) $msg - Mensagem
     * \return (\FW\Utils\MessageContainer).
     */
    public function add($key, $msg)
    {
        if (is_array($msg)) {
            foreach ($msg as $m) {
                $this->add($key, $m);
            }

            return $this;
        }

        if ($this->unique($key, $msg)) {
            $this->messages[$key][] = $msg;
        }

        return $this;
    }

    /**
     * \brief Concatena este container de mensagens com outro
     * \param [in] (\FW\Utils\MessageContainer $messageContainer).
     */
    public function merge(MessageContainer $messageContainer)
    {
        $this->setMessages($messageContainer->getMessages());
    }

    /**
     * \brief Seta as mensagens do container
     * \param [in] (rray) $messages
     * \return (\FW\Utils\MessageContainer).
     */
    public function setMessages(array $messages)
    {
        foreach ($messages as $key => $msg) {
            $this->add($key, $msg);
        }

        return $this;
    }

    /**
     * \brief Retorna as mensagens do container
     * \return (array).
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * \brief Destroi as mensagens com essa chave de identificação
     * \param [in] (string) $key.
     */
    public function forget($key)
    {
        unset($this->messages[$key]);
    }

    /**
     * \brief Retorna se a mensagem é única para a chave identificadora
     * \param [in] (string) $key - Chave identificadora
     * \param [in] (string) $msg - Mensagem
     * \return (boolean).
     */
    protected function unique($key, $msg)
    {
        return !isset($this->messages[$key]) || !in_array($msg, $this->messages[$key]);
    }

    /**
     * \brief Compila a mensagem substituindo os placeholders pelas mensagens em sí
     * \param [in] (string) $key - Chave identificadora
     * \param [in] (string) $msg - Mensagem
     * \param [in] (variant) $format - Formato com o placeholder
     * \return (array).
     */
    protected function formatMsg($key, $msg, $format = null)
    {
        $msgs = [];
        $params = [':key', ':msg'];

        foreach ((array) $msg as $m) {
            $msgs[] = str_replace($params, [$key, $m], $this->format($format));
        }

        return $msgs;
    }

    /**
     * \brief Retorna o formato com o placeholder
     * \param [in] (string) $format
     * \return (string).
     */
    protected function format($format = null)
    {
        if (!$format) {
            return $this->format;
        }

        return $format;
    }

    /**
     * \brief mesmo que 'has()'
     * \param [in] (variant) $offset
     * \return (variant).
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * \brief mesmo que 'get()'
     * \param [in] (variant) $offset
     * \return (variant).
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * \brief Mesmo que 'add()'
     * \param [in] (variant) $offset
     * \param [in] (variant) $value.
     */
    public function offsetSet($offset, $value)
    {
        $this->add($offset, $value);
    }

    /**
     * \brief Mesmo que 'forget()'
     * \param [in] (variant) $offset.
     */
    public function offsetUnset($offset)
    {
        $this->forget($offset);
    }
}
