<?php

namespace Rajifsismedika\Validation;

use Rajifsismedika\Validation\MissingRequiredParameterException;

abstract class Rule
{
    /** @var string */
    protected $key;

    /** @var \Rajifsismedika\Validation\Attribute|null */
    protected $attribute;

    /** @var \Rajifsismedika\Validation\Validation|null */
    protected $validation;

    /** @var bool */
    protected $implicit = false;

    /** @var array */
    protected $params = [];

    /** @var array */
    protected $paramsTexts = [];

    /** @var array */
    protected $fillableParams = [];

    /** @var string */
    protected $message = "The :attribute is invalid";

    abstract public function check($value);

    /**
     * Set Validation class instance
     *
     * @param \Rajifsismedika\Validation\Validation $validation
     * @return void
     */
    public function setValidation(Validation $validation)
    {
        $this->validation = $validation;
    }

    /**
     * Set key
     *
     * @param $key
     * @return void
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key ?: get_class($this);
    }

    /**
     * Set attribute
     *
     * @param \Rajifsismedika\Validation\Attribute $attribute
     * @return void
     */
    public function setAttribute(Attribute $attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * Get attribute
     *
     * @return \Rajifsismedika\Validation\Attribute|null
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Get parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * Set params
     *
     * @param $params
     * @return \Rajifsismedika\Validation\Rule
     */
    public function setParameters($params)
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * Set parameters
     *
     * @param $key
     * @param mixed $value
     * @return \Rajifsismedika\Validation\Rule
     */
    public function setParameter($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Fill $params to $this->params
     *
     * @param $params
     * @return \Rajifsismedika\Validation\Rule
     */
    public function fillParameters($params)
    {
        foreach ($this->fillableParams as $key) {
            if (empty($params)) {
                break;
            }
            $this->params[$key] = array_shift($params);
        }
        return $this;
    }

    /**
     * Get parameter from given $key, return null if it not exists
     *
     * @param $key
     * @return mixed
     */
    public function parameter($key)
    {
        return isset($this->params[$key])? $this->params[$key] : null;
    }

    /**
     * Set parameter text that can be displayed in error message using ':param_key'
     *
     * @param $key
     * @param $text
     * @return void
     */
    public function setParameterText($key, $text)
    {
        $this->paramsTexts[$key] = $text;
    }

    /**
     * Get $paramsTexts
     *
     * @return array
     */
    public function getParametersTexts()
    {
        return $this->paramsTexts;
    }

    /**
     * Check whether this rule is implicit
     *
     * @return boolean
     */
    public function isImplicit()
    {
        return $this->implicit;
    }

    /**
     * Just alias of setMessage
     *
     * @param $message
     * @return \Rajifsismedika\Validation\Rule
     */
    public function message($message)
    {
        return $this->setMessage($message);
    }

    /**
     * Set message
     *
     * @param $message
     * @return \Rajifsismedika\Validation\Rule
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Check given $params must be exists
     *
     * @param $params
     * @return void
     * @throws \Rajifsismedika\Validation\MissingRequiredParameterException
     */
    protected function requireParameters($params)
    {
        foreach ($params as $param) {
            if (!isset($this->params[$param])) {
                $rule = $this->getKey();
                throw new MissingRequiredParameterException("Missing required parameter '{$param}' on rule '{$rule}'");
            }
        }
    }
}
