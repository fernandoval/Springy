<?php
/**
 * Container class for text messages.
 *
 * @copyright 2014-2018 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   0.2.0.4
 */

namespace Springy\Utils;

use ArrayAccess;

/**
 * Container class for text messages.
 */
class MessageContainer implements ArrayAccess
{
    /// Array que guarda as mensagens adicionadas no container
    protected $messages;
    /// Formato do placeholder da mensagem
    protected $format = ':msg';

    /**
     * Constructor.
     *
     * @param array $messages
     */
    public function __construct(array $messages = [])
    {
        $this->messages = [];
        $this->setMessages($messages);
    }

    /**
     * Checks if there is any message stored in the container.
     *
     * @return bool
     */
    public function hasAny()
    {
        return !empty($this->messages);
    }

    /**
     * Checks if any messages are stored with the identification key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->messages[$key]) && $this->messages[$key] != '';
    }

    /**
     * Gets the first message stored with the key.
     *
     * @param string $key    identification key.
     * @param string $format placeholder format.
     *
     * @return mixed
     */
    public function first($key, $format = ':msg')
    {
        if ($this->has($key)) {
            $first = $this->formatMsg($key, reset($this->messages[$key]), $format);

            return $first[0];
        }
    }

    /**
     * Gets all messages stored with the key.
     *
     * @param string $key    identification key.
     * @param string $format placeholder format.
     *
     * @return array
     */
    public function get($key, $format = ':msg')
    {
        if ($this->has($key)) {
            return $this->formatMsg($key, $this->messages[$key], $format);
        }

        return [];
    }

    /**
     * Gets all stored messages.
     *
     * @param string $format placeholder format.
     *
     * @return array
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
     * Adds a message to the identifying key.
     *
     * @param string $key identification key.
     * @param string $msg the message.
     *
     * @return Springy\Utils\MessageContainer
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
     * Concatenates this message container with another.
     *
     * @param \Springy\Utils\MessageContainer $messageContainer.
     *
     * @return void
     */
    public function merge(self $messageContainer)
    {
        $this->setMessages($messageContainer->getMessages());
    }

    /**
     * Sets the messages in the container.
     *
     * @param array $messages
     *
     * @return \Springy\Utils\MessageContainer
     */
    public function setMessages(array $messages)
    {
        foreach ($messages as $key => $msg) {
            $this->add($key, $msg);
        }

        return $this;
    }

    /**
     * Gets the messages in the container.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Destroy the messages with this key.
     *
     * @param string $key
     */
    public function forget($key)
    {
        unset($this->messages[$key]);
    }

    /**
     * Returns whether the message is unique to the identifying key.
     *
     * @param string $key identification key.
     * @param string $msg the message
     *
     * @return bool
     */
    protected function unique($key, $msg)
    {
        return !isset($this->messages[$key]) || !in_array($msg, $this->messages[$key]);
    }

    /**
     * Compiles the message by replacing the placeholders with the messages themselves.
     *
     * @param string $key    identification key.
     * @param string $msg    the message.
     * @param mixed  $format placeholder format.
     *
     * @return array
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
     * Gets the format with the placeholder.
     *
     * @param string $format
     *
     * @return string
     */
    protected function format($format = null)
    {
        if (!$format) {
            return $this->format;
        }

        return $format;
    }

    /**
     * An alias for 'has()'.
     *
     * @see has
     * @deprecated 0.3
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * An alias for 'get()'.
     *
     * @see get
     * @deprecated 0.3
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * An alias for 'add()'.
     *
     * @see add
     * @deprecated 0.3
     */
    public function offsetSet($offset, $value)
    {
        $this->add($offset, $value);
    }

    /**
     * An alias for 'forget()'.
     *
     * @see forget
     * @deprecated 0.3
     */
    public function offsetUnset($offset)
    {
        $this->forget($offset);
    }
}
