<?php

namespace Rajifsismedika\Validation;

use Closure;
use Rajifsismedika\Validation\Rules\Interfaces\BeforeValidate;
use Rajifsismedika\Validation\Rules\Interfaces\ModifyValue;
use Rajifsismedika\Validation\Rules\Required;

class Validation
{
    use Traits\TranslationsTrait, Traits\MessagesTrait;

    /** @var mixed */
    protected $validator;

    /** @var array */
    protected $inputs = [];

    /** @var array */
    protected $attributes = [];

    /** @var array */
    protected $aliases = [];

    /** @var string */
    protected $messageSeparator = ':';

    /** @var array */
    protected $validData = [];

    /** @var array */
    protected $invalidData = [];

    /** @var ErrorBag */
    public $errors;

    /**
     * Constructor
     *
     * @param \Rajifsismedika\Validation\Validator $validator
     * @param $inputs
     * @param $rules
     * @param $messages
     * @return void
     */
    public function __construct(
        Validator $validator,
        $inputs,
        $rules,
        $messages = []
    ) {
        $this->validator = $validator;
        $this->inputs = $this->resolveInputAttributes($inputs);
        $this->messages = $messages;
        $this->errors = new ErrorBag;
        foreach ($rules as $attributeKey => $rules) {
            $this->addAttribute($attributeKey, $rules);
        }
    }

    /**
     * Add attribute rules
     *
     * @param $attributeKey
     * @param string|$rules
     * @return void
     */
    public function addAttribute($attributeKey, $rules)
    {
        $resolvedRules = $this->resolveRules($rules);
        $attribute = new Attribute($this, $attributeKey, $this->getAlias($attributeKey), $resolvedRules);
        $this->attributes[$attributeKey] = $attribute;
    }

    /**
     * Get attribute by key
     *
     * @param $attributeKey
     * @return null|\Rajifsismedika\Validation\Attribute
     */
    public function getAttribute($attributeKey)
    {
        return isset($this->attributes[$attributeKey])? $this->attributes[$attributeKey] : null;
    }

    /**
     * Run validation
     *
     * @param $inputs
     * @return void
     */
    public function validate($inputs = [])
    {
        $this->errors = new ErrorBag; // reset error bag
        $this->inputs = array_merge($this->inputs, $this->resolveInputAttributes($inputs));

        // Before validation hooks
        foreach ($this->attributes as $attributeKey => $attribute) {
            foreach ($attribute->getRules() as $rule) {
                if ($rule instanceof BeforeValidate) {
                    $rule->beforeValidate();
                }
            }
        }

        foreach ($this->attributes as $attributeKey => $attribute) {
            $this->validateAttribute($attribute);
        }
    }

    /**
     * Get ErrorBag instance
     *
     * @return \Rajifsismedika\Validation\ErrorBag
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Validate attribute
     *
     * @param \Rajifsismedika\Validation\Attribute $attribute
     * @return void
     */
    protected function validateAttribute(Attribute $attribute)
    {
        if ($this->isArrayAttribute($attribute)) {
            $attributes = $this->parseArrayAttribute($attribute);
            foreach ($attributes as $i => $attr) {
                $this->validateAttribute($attr);
            }
            return;
        }

        $attributeKey = $attribute->getKey();
        $rules = $attribute->getRules();

        $value = $this->getValue($attributeKey);
        $isEmptyValue = $this->isEmptyValue($value);

        if ($attribute->hasRule('nullable') && $isEmptyValue) {
            $rules = [];
        }

        $isValid = true;
        foreach ($rules as $ruleValidator) {
            $ruleValidator->setAttribute($attribute);

            if ($ruleValidator instanceof ModifyValue) {
                $value = $ruleValidator->modifyValue($value);
                $isEmptyValue = $this->isEmptyValue($value);
            }

            $valid = $ruleValidator->check($value);

            if ($isEmptyValue and $this->ruleIsOptional($attribute, $ruleValidator)) {
                continue;
            }

            if (!$valid) {
                $isValid = false;
                $this->addError($attribute, $value, $ruleValidator);
                if ($ruleValidator->isImplicit()) {
                    break;
                }
            }
        }

        if ($isValid) {
            $this->setValidData($attribute, $value);
        } else {
            $this->setInvalidData($attribute, $value);
        }
    }

    /**
     * Check whether given $attribute is array attribute
     *
     * @param \Rajifsismedika\Validation\Attribute $attribute
     * @return bool
     */
    protected function isArrayAttribute(Attribute $attribute)
    {
        $key = $attribute->getKey();
        return strpos($key, '*') !== false;
    }

    /**
     * Parse array attribute into it's child attributes
     *
     * @param \Rajifsismedika\Validation\Attribute $attribute
     * @return array
     */
    protected function parseArrayAttribute(Attribute $attribute)
    {
        $attributeKey = $attribute->getKey();
        $data = Helper::arrayDot($this->initializeAttributeOnData($attributeKey));

        $pattern = str_replace('\*', '([^\.]+)', preg_quote($attributeKey));

        $data = array_merge($data, $this->extractValuesForWildcards(
            $data,
            $attributeKey
        ));

        $attributes = [];

        foreach ($data as $key => $value) {
            if ((bool) preg_match('/^'.$pattern.'\z/', $key, $match)) {
                $attr = new Attribute($this, $key, null, $attribute->getRules());
                $attr->setPrimaryAttribute($attribute);
                $attr->setKeyIndexes(array_slice($match, 1));
                $attributes[] = $attr;
            }
        }

        // set other attributes to each attributes
        foreach ($attributes as $i => $attr) {
            $otherAttributes = $attributes;
            unset($otherAttributes[$i]);
            $attr->setOtherAttributes($otherAttributes);
        }

        return $attributes;
    }

    /**
     * Gather a copy of the attribute data filled with any missing attributes.
     * Adapted from: https://github.com/illuminate/validation/blob/v5.3.23/Validator.php#L334
     *
     * @param  string  $attribute
     * @return array
     */
    protected function initializeAttributeOnData($attributeKey)
    {
        $explicitPath = $this->getLeadingExplicitAttributePath($attributeKey);

        $data = $this->extractDataFromPath($explicitPath);

        $asteriskPos = strpos($attributeKey, '*');

        if (false === $asteriskPos || $asteriskPos === (mb_strlen($attributeKey, 'UTF-8') - 1)) {
            return $data;
        }

        return Helper::arraySet($data, $attributeKey, null, true);
    }

    /**
     * Get all of the exact attribute values for a given wildcard attribute.
     * Adapted from: https://github.com/illuminate/validation/blob/v5.3.23/Validator.php#L354
     *
     * @param  array  $data
     * @param  string  $attributeKey
     * @return array
     */
    public function extractValuesForWildcards($data, $attributeKey)
    {
        $keys = [];

        $pattern = str_replace('\*', '[^\.]+', preg_quote($attributeKey));

        foreach ($data as $key => $value) {
            if ((bool) preg_match('/^'.$pattern.'/', $key, $matches)) {
                $keys[] = $matches[0];
            }
        }

        $keys = array_unique($keys);

        $data = [];

        foreach ($keys as $key) {
            $data[$key] = Helper::arrayGet($this->inputs, $key);
        }

        return $data;
    }

    /**
     * Get the explicit part of the attribute name.
     * Adapted from: https://github.com/illuminate/validation/blob/v5.3.23/Validator.php#L2817
     *
     * E.g. 'foo.bar.*.baz' -> 'foo.bar'
     *
     * Allows us to not spin through all of the flattened data for some operations.
     *
     * @param  string  $attributeKey
     * @return string|null null when root wildcard
     */
    protected function getLeadingExplicitAttributePath($attributeKey)
    {
        return rtrim(explode('*', $attributeKey)[0], '.') ?: null;
    }

    /**
     * Extract data based on the given dot-notated path.
     * Adapted from: https://github.com/illuminate/validation/blob/v5.3.23/Validator.php#L2830
     *
     * Used to extract a sub-section of the data for faster iteration.
     *
     * @param  string|null $attributeKey
     * @return array
     */
    protected function extractDataFromPath($attributeKey)
    {
        $results = [];

        $value = Helper::arrayGet($this->inputs, $attributeKey, '__missing__');

        if ($value != '__missing__') {
            Helper::arraySet($results, $attributeKey, $value);
        }

        return $results;
    }

    /**
     * Add error to the $this->errors
     *
     * @param \Rajifsismedika\Validation\Attribute $attribute
     * @param mixed $value
     * @param \Rajifsismedika\Validation\Rule $ruleValidator
     * @return void
     */
    protected function addError(Attribute $attribute, $value, Rule $ruleValidator)
    {
        $ruleName = $ruleValidator->getKey();
        $message = $this->resolveMessage($attribute, $value, $ruleValidator);

        $this->errors->add($attribute->getKey(), $ruleName, $message);
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

    /**
     * Check the rule is optional
     *
     * @param \Rajifsismedika\Validation\Attribute $attribute
     * @param \Rajifsismedika\Validation\Rule $rule
     * @return bool
     */
    protected function ruleIsOptional(Attribute $attribute, Rule $rule)
    {
        return false === $attribute->isRequired() and
            false === $rule->isImplicit() and
            false === $rule instanceof Required;
    }

    /**
     * Resolve attribute name
     *
     * @param \Rajifsismedika\Validation\Attribute $attribute
     * @return string
     */
    protected function resolveAttributeName(Attribute $attribute)
    {
        $primaryAttribute = $attribute->getPrimaryAttribute();
        if (isset($this->aliases[$attribute->getKey()])) {
            return $this->aliases[$attribute->getKey()];
        } elseif ($primaryAttribute and isset($this->aliases[$primaryAttribute->getKey()])) {
            return $this->aliases[$primaryAttribute->getKey()];
        } elseif ($this->validator->isUsingHumanizedKey()) {
            return $attribute->getHumanizedKey();
        } else {
            return $attribute->getKey();
        }
    }

    /**
     * Resolve message
     *
     * @param \Rajifsismedika\Validation\Attribute $attribute
     * @param mixed $value
     * @param \Rajifsismedika\Validation\Rule $validator
     * @return mixed
     */
    protected function resolveMessage(Attribute $attribute, $value, Rule $validator)
    {
        $primaryAttribute = $attribute->getPrimaryAttribute();
        $params = array_merge($validator->getParameters(), $validator->getParametersTexts());
        $attributeKey = $attribute->getKey();
        $ruleKey = $validator->getKey();
        $alias = $attribute->getAlias() ?: $this->resolveAttributeName($attribute);
        $message = $validator->getMessage(); // default rule message
        $messageKeys = [
            $attributeKey.$this->messageSeparator.$ruleKey,
            $attributeKey,
            $ruleKey
        ];

        if ($primaryAttribute) {
            // insert primaryAttribute keys
            // $messageKeys = [
            //     $attributeKey.$this->messageSeparator.$ruleKey,
            //     >> here [1] <<
            //     $attributeKey,
            //     >> and here [3] <<
            //     $ruleKey
            // ];
            $primaryAttributeKey = $primaryAttribute->getKey();
            array_splice($messageKeys, 1, 0, $primaryAttributeKey.$this->messageSeparator.$ruleKey);
            array_splice($messageKeys, 3, 0, $primaryAttributeKey);
        }

        foreach ($messageKeys as $key) {
            if (isset($this->messages[$key])) {
                $message = $this->messages[$key];
                break;
            }
        }

        // Replace message params
        $vars = array_merge($params, [
            'attribute' => $alias,
            'value' => $value,
        ]);

        foreach ($vars as $key => $value) {
            $value = $this->stringify($value);
            $message = str_replace(':'.$key, $value, $message);
        }

        // Replace key indexes
        $keyIndexes = $attribute->getKeyIndexes();
        foreach ($keyIndexes as $pathIndex => $index) {
            $replacers = [
                "[{$pathIndex}]" => $index,
            ];

            if (is_numeric($index)) {
                $replacers["{{$pathIndex}}"] = $index + 1;
            }

            $message = str_replace(array_keys($replacers), array_values($replacers), $message);
        }

        return $message;
    }

    /**
     * Stringify $value
     *
     * @param mixed $value
     * @return string
     */
    protected function stringify($value)
    {
        if (is_string($value) || is_numeric($value)) {
            return $value;
        } elseif (is_array($value) || is_object($value)) {
            return json_encode($value);
        } else {
            return '';
        }
    }

    /**
     * Resolve $rules
     *
     * @param mixed $rules
     * @return array
     */
    protected function resolveRules($rules)
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $resolvedRules = [];
        $validatorFactory = $this->getValidator();

        foreach ($rules as $i => $rule) {
            if (empty($rule)) {
                continue;
            }
            $params = [];

            if (is_string($rule)) {
                list($rulename, $params) = $this->parseRule($rule);
                $validator = call_user_func_array($validatorFactory, array_merge([$rulename], $params));
            } elseif ($rule instanceof Rule) {
                $validator = $rule;
            } elseif ($rule instanceof Closure) {
                $validator = call_user_func_array($validatorFactory, ['callback', $rule]);
            } else {
                $ruleName = is_object($rule) ? get_class($rule) : gettype($rule);
                $message = "Rule must be a string, Closure or '".Rule::class."' instance. ".$ruleName." given";
                throw new \Exception();
            }

            $resolvedRules[] = $validator;
        }

        return $resolvedRules;
    }

    /**
     * Parse $rule
     *
     * @param $rule
     * @return array
     */
    protected function parseRule($rule)
    {
        $exp = explode(':', $rule, 2);
        $rulename = $exp[0];
        if ($rulename !== 'regex') {
            $params = isset($exp[1])? explode(',', $exp[1]) : [];
        } else {
            $params = [$exp[1]];
        }

        return [$rulename, $params];
    }

    /**
     * Given $attributeKey and $alias then assign alias
     *
     * @param mixed $attributeKey
     * @param mixed $alias
     * @return void
     */
    public function setAlias($attributeKey, $alias)
    {
        $this->aliases[$attributeKey] = $alias;
    }

    /**
     * Get attribute alias from given key
     *
     * @param mixed $attributeKey
     * @return string|null
     */
    public function getAlias($attributeKey)
    {
        return isset($this->aliases[$attributeKey])? $this->aliases[$attributeKey] : null;
    }

    /**
     * Set attributes aliases
     *
     * @param $aliases
     * @return void
     */
    public function setAliases($aliases)
    {
        $this->aliases = array_merge($this->aliases, $aliases);
    }

    /**
     * Check validations are passed
     *
     * @return bool
     */
    public function passes()
    {
        return $this->errors->count() == 0;
    }

    /**
     * Check validations are failed
     *
     * @return bool
     */
    public function fails()
    {
        return !$this->passes();
    }

    /**
     * Given $key and get value
     *
     * @param $key
     * @return mixed
     */
    public function getValue($key)
    {
        return Helper::arrayGet($this->inputs, $key);
    }

    /**
     * Set input value
     *
     * @param $key
     * @param mixed $value
     * @return void
     */
    public function setValue($key, $value)
    {
        Helper::arraySet($this->inputs, $key, $value);
    }

    /**
     * Given $key and check value is exsited
     *
     * @param $key
     * @return boolean
     */
    public function hasValue($key)
    {
        return Helper::arrayHas($this->inputs, $key);
    }

    /**
     * Get Validator class instance
     *
     * @return \Rajifsismedika\Validation\Validator
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Given $inputs and resolve input attributes
     *
     * @param $inputs
     * @return array
     */
    protected function resolveInputAttributes($inputs)
    {
        $resolvedInputs = [];
        foreach ($inputs as $key => $rules) {
            $exp = explode(':', $key);

            if (count($exp) > 1) {
                // set attribute alias
                $this->aliases[$exp[0]] = $exp[1];
            }

            $resolvedInputs[$exp[0]] = $rules;
        }

        return $resolvedInputs;
    }

    /**
     * Get validated data
     *
     * @return array
     */
    public function getValidatedData()
    {
        return array_merge($this->validData, $this->invalidData);
    }

    /**
     * Set valid data
     *
     * @param \Rajifsismedika\Validation\Attribute $attribute
     * @param mixed $value
     * @return void
     */
    protected function setValidData(Attribute $attribute, $value)
    {
        $key = $attribute->getKey();
        if ($attribute->isArrayAttribute() || $attribute->isUsingDotNotation()) {
            Helper::arraySet($this->validData, $key, $value);
            Helper::arrayUnset($this->invalidData, $key);
        } else {
            $this->validData[$key] = $value;
        }
    }

    /**
     * Get valid data
     *
     * @return array
     */
    public function getValidData()
    {
        return $this->validData;
    }

    /**
     * Set invalid data
     *
     * @param \Rajifsismedika\Validation\Attribute $attribute
     * @param mixed $value
     * @return void
     */
    protected function setInvalidData(Attribute $attribute, $value)
    {
        $key = $attribute->getKey();
        if ($attribute->isArrayAttribute() || $attribute->isUsingDotNotation()) {
            Helper::arraySet($this->invalidData, $key, $value);
            Helper::arrayUnset($this->validData, $key);
        } else {
            $this->invalidData[$key] = $value;
        }
    }

    /**
     * Get invalid data
     *
     * @return void
     */
    public function getInvalidData()
    {
        return $this->invalidData;
    }
}
