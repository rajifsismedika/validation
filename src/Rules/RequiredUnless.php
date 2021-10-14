<?php

namespace Rajifsismedika\Validation\Rules;

use Rajifsismedika\Validation\Rule;

class RequiredUnless extends Required
{
    /** @var bool */
    protected $implicit = true;

    /** @var string */
    protected $message = "The :attribute is required";

    /**
     * Given $params and assign the $this->params
     *
     * @param $params
     * @return self
     */
    public function fillParameters($params)
    {
        $this->params['field'] = array_shift($params);
        $this->params['values'] = $params;
        return $this;
    }

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     */
    public function check($value)
    {
        $this->requireParameters(['field', 'values']);

        $anotherAttribute = $this->parameter('field');
        $definedValues = $this->parameter('values');
        $anotherValue = $this->getAttribute()->getValue($anotherAttribute);

        $validator = $this->validation->getValidator();
        $requiredValidator = $validator('required');

        if (!in_array($anotherValue, $definedValues)) {
            $this->setAttributeAsRequired();
            return $requiredValidator->check($value, []);
        }

        return true;
    }
}
