<?php

namespace Rajifsismedika\Validation\Rules;

use Rajifsismedika\Validation\Rule;

class Ip extends Rule
{

    /** @var string */
    protected $message = "The :attribute is not valid IP Address";

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     */
    public function check($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
}
