<?php

namespace Rajifsismedika\Validation\Traits;

trait MessagesTrait
{

    /** @var array */
    protected $messages = [];

    /**
     * Given $key and $message to set message
     *
     * @param mixed $key
     * @param mixed $message
     * @return void
     */
    public function setMessage($key, $message)
    {
        $this->messages[$key] = $message;
    }

    /**
     * Given $messages and set multiple messages
     *
     * @param $messages
     * @return void
     */
    public function setMessages($messages)
    {
        $this->messages = array_merge($this->messages, $messages);
    }

    /**
     * Given message from given $key
     *
     * @param $key
     * @return string
     */
    public function getMessage($key)
    {
        return array_key_exists($key, $this->messages) ? $this->messages[$key] : $key;
    }

    /**
     * Get all $messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
