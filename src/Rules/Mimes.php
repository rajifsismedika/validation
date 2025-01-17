<?php

namespace Rajifsismedika\Validation\Rules;

use Rajifsismedika\Validation\Helper;
use Rajifsismedika\Validation\MimeTypeGuesser;
use Rajifsismedika\Validation\Rule;

class Mimes extends Rule
{
    use Traits\FileTrait;

    /** @var string */
    protected $message = "The :attribute file type must be :allowed_types";

    /** @var string|int */
    protected $maxSize = null;

    /** @var string|int */
    protected $minSize = null;

    /** @var array */
    protected $allowedTypes = [];

    /**
     * Given $params and assign $this->params
     *
     * @param $params
     * @return self
     */
    public function fillParameters($params)
    {
        $this->allowTypes($params);
        return $this;
    }

    /**
     * Given $types and assign $this->params
     *
     * @param mixed $types
     * @return self
     */
    public function allowTypes($types)
    {
        if (is_string($types)) {
            $types = explode('|', $types);
        }

        $this->params['allowed_types'] = $types;

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
        $allowedTypes = $this->parameter('allowed_types');

        if ($allowedTypes) {
            $or = $this->validation ? $this->validation->getTranslation('or') : 'or';
            $this->setParameterText('allowed_types', Helper::join(Helper::wraps($allowedTypes, "'"), ', ', ", {$or} "));
        }

        // below is Required rule job
        if (!$this->isValueFromUploadedFiles($value) or $value['error'] == UPLOAD_ERR_NO_FILE) {
            return true;
        }

        if (!$this->isUploadedFile($value)) {
            return false;
        }

        // just make sure there is no error
        if ($value['error']) {
            return false;
        }

        if (!empty($allowedTypes)) {
            $guesser = new MimeTypeGuesser;
            $ext = $guesser->getExtension($value['type']);
            unset($guesser);

            if (!in_array($ext, $allowedTypes)) {
                return false;
            }
        }

        return true;
    }
}
