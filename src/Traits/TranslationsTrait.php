<?php

namespace Rajifsismedika\Validation\Traits;

trait TranslationsTrait
{

    /** @var array */
    protected $translations = [];

    /**
     * Given $key and $translation to set translation
     *
     * @param mixed $key
     * @param mixed $translation
     * @return void
     */
    public function setTranslation($key, $translation)
    {
        $this->translations[$key] = $translation;
    }

    /**
     * Given $translations and set multiple translations
     *
     * @param $translations
     * @return void
     */
    public function setTranslations($translations)
    {
        $this->translations = array_merge($this->translations, $translations);
    }

    /**
     * Given translation from given $key
     *
     * @param $key
     * @return string
     */
    public function getTranslation($key)
    {
        return array_key_exists($key, $this->translations) ? $this->translations[$key] : $key;
    }

    /**
     * Get all $translations
     *
     * @return array
     */
    public function getTranslations()
    {
        return $this->translations;
    }
}
