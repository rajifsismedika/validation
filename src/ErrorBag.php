<?php

namespace Rajifsismedika\Validation;

class ErrorBag
{

    /** @var array */
    protected $messages = [];

    /**
     * Constructor
     *
     * @param $messages
     * @return void
     */
    public function __construct($messages = [])
    {
        $this->messages = $messages;
    }

    /**
     * Add message for given key and rule
     *
     * @param $key
     * @param $rule
     * @param $message
     * @return void
     */
    public function add($key, $rule, $message)
    {
        if (!isset($this->messages[$key])) {
            $this->messages[$key] = [];
        }

        $this->messages[$key][$rule] = $message;
    }

    /**
     * Get messages count
     *
     * @return int
     */
    public function count()
    {
        return count($this->all());
    }

    /**
     * Check given key is existed
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        list($key, $ruleName) = $this->parsekey($key);
        if ($this->isWildcardKey($key)) {
            $messages = $this->filterMessagesForWildcardKey($key, $ruleName);
            return count(Helper::arrayDot($messages)) > 0;
        } else {
            $messages = isset($this->messages[$key])? $this->messages[$key] : null;

            if (!$ruleName) {
                return !empty($messages);
            } else {
                return !empty($messages) and isset($messages[$ruleName]);
            }
        }
    }

    /**
     * Get the first value of array
     *
     * @param $key
     * @return mixed
     */
    public function first($key)
    {
        list($key, $ruleName) = $this->parsekey($key);
        if ($this->isWildcardKey($key)) {
            $messages = $this->filterMessagesForWildcardKey($key, $ruleName);
            $flattenMessages = Helper::arrayDot($messages);
            return array_shift($flattenMessages);
        } else {
            $keyMessages = isset($this->messages[$key])? $this->messages[$key] : [];

            if (empty($keyMessages)) {
                return null;
            }

            if ($ruleName) {
                return isset($keyMessages[$ruleName])? $keyMessages[$ruleName] : null;
            } else {
                return array_shift($keyMessages);
            }
        }
    }

    /**
     * Get messages from given key, can be use custom format
     *
     * @param $key
     * @param $format
     * @return array
     */
    public function get($key, $format = ':message')
    {
        list($key, $ruleName) = $this->parsekey($key);
        $results = [];
        if ($this->isWildcardKey($key)) {
            $messages = $this->filterMessagesForWildcardKey($key, $ruleName);
            foreach ($messages as $explicitKey => $keyMessages) {
                foreach ($keyMessages as $rule => $message) {
                    $results[$explicitKey][$rule] = $this->formatMessage($message, $format);
                }
            }
        } else {
            $keyMessages = isset($this->messages[$key])? $this->messages[$key] : [];
            foreach ($keyMessages as $rule => $message) {
                if ($ruleName and $ruleName != $rule) {
                    continue;
                }
                $results[$rule] = $this->formatMessage($message, $format);
            }
        }

        return $results;
    }

    /**
     * Get all messages
     *
     * @param $format
     * @return array
     */
    public function all($format = ':message')
    {
        $messages = $this->messages;
        $results = [];
        foreach ($messages as $key => $keyMessages) {
            foreach ($keyMessages as $message) {
                $results[] = $this->formatMessage($message, $format);
            }
        }
        return $results;
    }

    /**
     * Get the first message from existing keys
     *
     * @param $format
     * @param boolean $dotNotation
     * @return array
     */
    public function firstOfAll($format = ':message', $dotNotation = false)
    {
        $messages = $this->messages;
        $results = [];
        foreach ($messages as $key => $keyMessages) {
            if ($dotNotation) {
                $results[$key] = $this->formatMessage(array_shift($messages[$key]), $format);
            } else {
                Helper::arraySet($results, $key, $this->formatMessage(array_shift($messages[$key]), $format));
            }
        }
        return $results;
    }

    /**
     * Get plain array messages
     *
     * @return array
     */
    public function toArray()
    {
        return $this->messages;
    }

    /**
     * Parse $key to get the array of $key and $ruleName
     *
     * @param $key
     * @return array
     */
    protected function parseKey($key)
    {
        $expl = explode(':', $key, 2);
        $key = $expl[0];
        $ruleName = isset($expl[1])? $expl[1] : null;
        return [$key, $ruleName];
    }

    /**
     * Check the $key is wildcard
     *
     * @param mixed $key
     * @return bool
     */
    protected function isWildcardKey($key)
    {
        return false !== strpos($key, '*');
    }

    /**
     * Filter messages with wildcard key
     *
     * @param $key
     * @param mixed  $ruleName
     * @return array
     */
    protected function filterMessagesForWildcardKey($key, $ruleName = null)
    {
        $messages = $this->messages;
        $pattern = preg_quote($key, '#');
        $pattern = str_replace('\*', '.*', $pattern);

        $filteredMessages = [];

        foreach ($messages as $k => $keyMessages) {
            if ((bool) preg_match('#^'.$pattern.'\z#u', $k) === false) {
                continue;
            }

            foreach ($keyMessages as $rule => $message) {
                if ($ruleName and $rule != $ruleName) {
                    continue;
                }
                $filteredMessages[$k][$rule] = $message;
            }
        }

        return $filteredMessages;
    }

    /**
     * Get formatted message
     *
     * @param $message
     * @param $format
     * @return string
     */
    protected function formatMessage($message, $format)
    {
        return str_replace(':message', $message, $format);
    }
}
