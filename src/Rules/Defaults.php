<?php

namespace Rajifsismedika\Validation\Rules;

use Rajifsismedika\Validation\Rule;
use Rajifsismedika\Validation\Rules\Interfaces\ModifyValue;

class Defaults extends Rule implements ModifyValue
{

    /** @var string */
    protected $message = "The :attribute default is :default";

    /** @var array */
    protected $fillableParams = ['default'];

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     */
    public function check($value)
    {
        $this->requireParameters($this->fillableParams);

        $default = $this->parameter('default');
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function modifyValue($value)
    {
        return $this->isEmptyValue($value) ? $this->parameter('default') : $value;
    }

    /**
     * Check $value is empty value
     *
     * @param mixed $value
     * @return boolean
     */
    protected function isEmptyValue($value)
    {
        $requiredValidator = new Required;
        return false === $requiredValidator->check($value, []);
    }
}
