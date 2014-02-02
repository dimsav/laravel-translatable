<?php namespace Dimsav\Translatable;

use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class Translatable extends Eloquent {

    public $translationModel;
    public $translationForeignKey;
    public $localeKey = 'locale';

    protected $translatedAttributes = array();
    protected $translationModels = array();

    public function getTranslationModelName() {
        return $this->translationModel ?: $this->getTranslationModelNameDefault();
    }

    public function getTranslationModelNameDefault() {
        return get_class($this) . 'Translation';
    }

    public function getRelationKey() {
        return $this->translationForeignKey ?: $this->getForeignKey();
    }

    public function getTranslationModel($locale = null) {
        $locale = $locale ?: \App::getLocale();

        if (isset ($this->translationModels[$locale])) {
            return $this->translationModels[$locale];
        }

        $translation = $this->hasMany($this->getTranslationModelName())
            ->where($this->localeKey, '=', $locale)
            ->first();
        return $this->translationModels[$locale] = $translation;
    }

    public function getAttribute($key) {
        return in_array($key, $this->translatedAttributes) ?
            $this->getTranslationModel()->$key :
            parent::getAttribute($key);
    }

}