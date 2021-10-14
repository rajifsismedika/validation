<?php

namespace Rajifsismedika\Validation;

class Attribute
{

    /** @var array */
    protected $rules = [];

    /** @var string */
    protected $key;

    /** @var string|null */
    protected $alias;

    /** @var \Rajifsismedika\Validation\Validation */
    protected $validation;

    /** @var bool */
    protected $required = false;

    /** @var \Rajifsismedika\Validation\Validation|null */
    protected $primaryAttribute = null;

    /** @var array */
    protected $otherAttributes = [];

    /** @var array */
    protected $keyIndexes = [];

    /**
     * Constructor
     *
     * @param \Rajifsismedika\Validation\Validation  $validation
     * @param string      $key
     * @param string|null $alias
     * @param array       $rules
     * @return void
     */
    public function __construct(
        Validation $validation,
        $key,
        $alias = null,
        $rules = []
    ) {
        $this->validation = $validation;
        $this->alias = $alias;
        $this->key = $key;
        foreach ($rules as $rule) {
            $this->addRule($rule);
        }
    }

    /**
     * Set the primary attribute
     *
     * @param \Rajifsismedika\Validation\Attribute $primaryAttribute
     * @return void
     */
    public function setPrimaryAttribute(Attribute $primaryAttribute)
    {
        $this->primaryAttribute = $primaryAttribute;
    }

    /**
     * Set key indexes
     *
     * @param $keyIndexes
     * @return void
     */
    public function setKeyIndexes($keyIndexes)
    {
        $this->keyIndexes = $keyIndexes;
    }

    /**
     * Get primary attributes
     *
     * @return \Rajifsismedika\Validation\Attribute|null
     */
    public function getPrimaryAttribute()
    {
        return $this->primaryAttribute;
    }

    /**
     * Set other attributes
     *
     * @param $otherAttributes
     * @return void
     */
    public function setOtherAttributes($otherAttributes)
    {
        $this->otherAttributes = [];
        foreach ($otherAttributes as $otherAttribute) {
            $this->addOtherAttribute($otherAttribute);
        }
    }

    /**
     * Add other attributes
     *
     * @param \Rajifsismedika\Validation\Attribute $otherAttribute
     * @return void
     */
    public function addOtherAttribute(Attribute $otherAttribute)
    {
        $this->otherAttributes[] = $otherAttribute;
    }

    /**
     * Get other attributes
     *
     * @return array
     */
    public function getOtherAttributes()
    {
        return $this->otherAttributes;
    }

    /**
     * Add rule
     *
     * @param \Rajifsismedika\Validation\Rule $rule
     * @return void
     */
    public function addRule(Rule $rule)
    {
        $rule->setAttribute($this);
        $rule->setValidation($this->validation);
        $this->rules[$rule->getKey()] = $rule;
    }

    /**
     * Get rule
     *
     * @param $ruleKey
     * @return void
     */
    public function getRule($ruleKey)
    {
        return $this->hasRule($ruleKey)? $this->rules[$ruleKey] : null;
    }

    /**
     * Get rules
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Check the $ruleKey has in the rule
     *
     * @param $ruleKey
     * @return bool
     */
    public function hasRule($ruleKey)
    {
        return isset($this->rules[$ruleKey]);
    }

    /**
     * Set required
     *
     * @param boolean $required
     * @return void
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * Set rule is required
     *
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Get key indexes
     *
     * @return array
     */
    public function getKeyIndexes()
    {
        return $this->keyIndexes;
    }

    /**
     * Get value
     *
     * @param string|null $key
     * @return mixed
     */
    public function getValue($key = null)
    {
        if ($key && $this->isArrayAttribute()) {
            $key = $this->resolveSiblingKey($key);
        }

        if (!$key) {
            $key = $this->getKey();
        }

        return $this->validation->getValue($key);
    }

    /**
     * Get that is array attribute
     *
     * @return boolean
     */
    public function isArrayAttribute()
    {
        return count($this->getKeyIndexes()) > 0;
    }

    /**
     * Check this attribute is using dot notation
     *
     * @return boolean
     */
    public function isUsingDotNotation()
    {
        return strpos($this->getKey(), '.') !== false;
    }

    /**
     * Resolve sibling key
     *
     * @param $key
     * @return string
     */
    public function resolveSiblingKey($key)
    {
        $indexes = $this->getKeyIndexes();
        $keys = explode("*", $key);
        $countAsterisks = count($keys) - 1;
        if (count($indexes) < $countAsterisks) {
            $indexes = array_merge($indexes, array_fill(0, $countAsterisks - count($indexes), "*"));
        }
        $args = array_merge([str_replace("*", "%s", $key)], $indexes);
        return call_user_func_array('sprintf', $args);
    }

    /**
     * Get humanize key
     *
     * @return string
     */
    public function getHumanizedKey()
    {
        $primaryAttribute = $this->getPrimaryAttribute();
        $key = str_replace('_', ' ', $this->key);

        // Resolve key from array validation
        if ($primaryAttribute) {
            $split = explode('.', $key);
            $key = implode(' ', array_map(function ($word) {
                if (is_numeric($word)) {
                    $word = $word + 1;
                }
                return Helper::snakeCase($word, ' ');
            }, $split));
        }

        return ucfirst($key);
    }

    /**
     * Set alias
     *
     * @param $alias
     * @return void
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * Get alias
     *
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }
}
