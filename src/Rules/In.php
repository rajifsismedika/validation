<?php

namespace Rajifsismedika\Validation\Rules;

use Rajifsismedika\Validation\Helper;
use Rajifsismedika\Validation\Rule;

class In extends Rule
{

    /** @var string */
    protected $message = "The :attribute only allows :allowed_values";

    /** @var bool */
    protected $strict = false;

    /**
     * Given $params and assign the $this->params
     *
     * @param $params
     * @return self
     */
    public function fillParameters($params)
    {
        if (count($params) == 1 && is_array($params[0])) {
            $params = $params[0];
        }
        $this->params['allowed_values'] = $params;
        return $this;
    }

    /**
     * Set strict value
     *
     * @param $strict
     * @return void
     */
    public function strict($strict = true)
    {
        $this->strict = $strict;
    }

    /**
     * Check $value is existed
     *
     * @param mixed $value
     * @return bool
     */
    public function check($value)
    {
        $this->requireParameters(['allowed_values']);

        $allowedValues = $this->parameter('allowed_values');

        $or = $this->validation ? $this->validation->getTranslation('or') : 'or';
        $allowedValuesText = Helper::join(Helper::wraps($allowedValues, "'"), ', ', ", {$or} ");
        $this->setParameterText('allowed_values', $allowedValuesText);

        return in_array($value, $allowedValues, $this->strict);
    }
}
