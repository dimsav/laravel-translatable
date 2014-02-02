<?php namespace Dimsav\Translatable;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;

abstract class Translatable extends Eloquent {

    public $translationModel;
    protected $translatedAttributes = array();
    protected $translationModels = array();

    public function getTranslationModelName() {
        return $this->translationModel ?: $this->getTranslationModelNameDefault();
    }

    public function getTranslationModelNameDefault() {
        return get_class($this) . 'Translation';
    }

}