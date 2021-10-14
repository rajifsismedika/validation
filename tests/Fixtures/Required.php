<?php

namespace Rajifsismedika\Validation\Tests;

use Rajifsismedika\Validation\Rule;

class Required extends Rule
{

    public function check($value)
    {
        return true;
    }
}
