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

    public function getTranslationModels() {
        $modelsFromDb = $this->hasMany($this->getTranslationModelName(), $this->getRelationKey())
            ->whereNotIn($this->localeKey, $this->getInstanciatedTranslationLocales())->get();
        foreach ($modelsFromDb as $modelFromDb) {
            $this->translationModels[$modelFromDb->getAttribute($this->localeKey)] = $modelFromDb;
        }
        return $this->translationModels;
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

    public function setAttribute($key, $value) {
        if (in_array($key, $this->translatedAttributes)) {
            $this->getTranslationModel()->$key = $value;
        }
        else {
            parent::setAttribute($key, $value);
        }
    }

    public function save(array $options = array()) {
        if (parent::save($options)) {
            return $this->saveTranslations();
        }
        return false;
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

    public function forceDelete() {
        $this->deleteTranslations();
        parent::forceDelete();
    }

    protected function isKeyReturningTranslationText($key) {
        return in_array($key, $this->translatedAttributes);
    }

    protected function getInstanciatedTranslationLocales() {
        return array_keys($this->translationModels);
    }

    protected function isKeyALocale($key) {
        $locales = $this->getLocales();
        return in_array($key, $locales);
    }

    protected function getLocales() {
        $config = \App::make('config');
        return $config->get('app.locales', array());
    }

    protected function saveTranslations() {
        $saved = true;
        foreach ($this->translationModels as $translation) {
            if ($saved && $this->isTranslationDirty($translation)){
                $translation->setAttribute($this->getRelationKey(), $this->getKey());
                $saved = $translation->save();
            }
        }
        return $saved;
    }

    protected function isTranslationDirty($translation) {
        $dirtyAttributes = $translation->getDirty();
        unset($dirtyAttributes[$this->localeKey]);
        return count($dirtyAttributes) > 0;
    }

    protected function getNewTranslationInsstance($locale) {
        $modelName = $this->getTranslationModelName();
        $translation = new $modelName;
        $translation->setAttribute($this->localeKey, $locale);
        return $translation;
    }

    protected function performDeleteOnModel() {
        if ( ! $this->softDelete) {
            $this->deleteTranslations();
        }
        parent::performDeleteOnModel();
    }

    protected function deleteTranslations() {
        foreach ($this->getTranslationModels() as $translation) {
            $translation->delete();
        }
    }

}