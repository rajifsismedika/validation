<?php

namespace Rajifsismedika\Validation\Rules;

use Rajifsismedika\Validation\Rule;

class Same extends Rule
{

    /** @var string */
    protected $message = "The :attribute must be same with :field";

    /** @var array */
    protected $fillableParams = ['field'];

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     */
    public function check($value)
    {
        $this->requireParameters($this->fillableParams);

        $field = $this->parameter('field');
        $anotherValue = $this->getAttribute()->getValue($field);

        return $value == $anotherValue;
    }
}
