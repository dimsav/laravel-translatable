<?php namespace Dimsav\Translatable;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\MassAssignmentException;

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
        $translation = $this->hasMany($this->getTranslationModelName(), $this->getRelationKey())
            ->where($this->localeKey, '=', $locale)
            ->first();
        $translation = $translation ?: $this->getNewTranslationInsstance($locale);
        return $this->translationModels[$locale] = $translation;
    }

    public function getAttribute($key) {
        if ($this->isKeyReturningTranslationText($key)) {
            return $this->getTranslationModel()->$key;
        }
        elseif ($this->isKeyALocale($key)) {
            return $this->getTranslationModel($key);
        }
       return parent::getAttribute($key);
    }

    private function isKeyReturningTranslationText($key) {
        return in_array($key, $this->translatedAttributes);
    }

    private function isKeyALocale($key) {
        $locales = $this->getLocales();
        return in_array($key, $locales);
    }

    private function getLocales() {
        $config = \App::make('config');
        return $config->get('app.locales', array());
    }

    public function setAttribute($key, $value) {
        if (in_array($key, $this->translatedAttributes)) {
            $this->getTranslationModel()->$key = $value;
        }
        else {
            parent::setAttribute($key, $value);
        }
    }

    public function saveTranslations() {
        foreach ($this->translationModels as $translation) {
            if ( $this->isTranslationDirty($translation)){
                $translation->setAttribute($this->getRelationKey(), $this->getKey());
                $translation->save();
            }
        }
    }

    private function isTranslationDirty($translation) {
        $dirtyAttributes = $translation->getDirty();
        unset($dirtyAttributes[$this->localeKey]);
        return count($dirtyAttributes) > 0;
    }

    private function getNewTranslationInsstance($locale) {
        $modelName = $this->getTranslationModelName();
        $translation = new $modelName;
        $translation->setAttribute($this->localeKey, $locale);
        return $translation;
    }

    public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();

        foreach ($attributes as $key => $values) {
            if ($this->isKeyALocale($key)) {
                $translation = $this->getTranslationModel($key);
                foreach ($values as $translationAttribute => $translationValue) {
                    if ($this->isFillable($translationAttribute)) {
                        $translation->$translationAttribute = $translationValue;
                        unset($attributes[$key]);
                    }
                    elseif ($totallyGuarded) {
                        throw new MassAssignmentException($key);
                    }
                }
            }
        }

        return parent::fill($attributes);
    }

}