<?php

namespace Rajifsismedika\Validation\Rules;

use Rajifsismedika\Validation\Rule;

class Uppercase extends Rule
{

    /** @var string */
    protected $message = "The :attribute must be uppercase";

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     */
    public function check($value)
    {
        return mb_strtoupper($value, mb_detect_encoding($value)) === $value;
    }
}
