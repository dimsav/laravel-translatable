<?php namespace Dimsav\Translatable;

use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;

trait Translatable {

    public function getTranslationModelName()
    {
        return $this->translationModel ?: $this->getTranslationModelNameDefault();
    }

    public function getTranslationModelNameDefault()
    {
        return get_class($this) . 'Translation';
    }

    public function getRelationKey()
    {
        return $this->translationForeignKey ?: $this->getForeignKey();
    }

    public function getLocaleKey()
    {
        return $this->localeKey ?: 'locale';
    }

    public function translations()
    {
        return $this->hasMany($this->getTranslationModelName(), $this->getRelationKey());
    }

    public function getTranslation($locale = null)
    {
        $locale = $locale ?: \App::getLocale();

        foreach ($this->translations as $translation)
        {
            if ($translation->getAttribute($this->getLocaleKey()) == $locale)
            {
                return $translation;
            }
        }
        $translation = $this->getNewTranslationInstance($locale);

        $this->translations->add($translation);

        return $translation;
    }

    public function getAttribute($key)
    {
        if ($this->isKeyReturningTranslationText($key))
        {
            return $this->getTranslation()->$key;
        }
        elseif ($this->isKeyALocale($key))
        {
            return $this->getTranslation($key);
        }
       return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->translatedAttributes))
        {
            $this->getTranslation()->$key = $value;
        }
        else
        {
            parent::setAttribute($key, $value);
        }
    }

    public function save(array $options = array())
    {
        if (parent::save($options))
        {
            return $this->saveTranslations();
        }
        return false;
    }

    public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();

        foreach ($attributes as $key => $values)
        {
            if ($this->isKeyALocale($key))
            {
                $translation = $this->getTranslation($key);

                foreach ($values as $translationAttribute => $translationValue)
                {
                    if ($this->isFillable($translationAttribute))
                    {
                        $translation->$translationAttribute = $translationValue;
                    }
                    elseif ($totallyGuarded)
                    {
                        throw new MassAssignmentException($key);
                    }
                }
                unset($attributes[$key]);
            }
        }

        return parent::fill($attributes);
    }

    public function forceDelete()
    {
        $this->deleteTranslations();
        parent::forceDelete();
    }

    protected function isKeyReturningTranslationText($key)
    {
        return in_array($key, $this->translatedAttributes);
    }

    protected function isKeyALocale($key)
    {
        $locales = $this->getLocales();
        return in_array($key, $locales);
    }

    protected function getLocales()
    {
        $config = \App::make('config');
        return $config->get('app.locales', array());
    }

    protected function saveTranslations()
    {
        $saved = true;
        foreach ($this->translations as $translation)
        {
            if ($saved && $this->isTranslationDirty($translation))
            {
                $translation->setAttribute($this->getRelationKey(), $this->getKey());
                $saved = $translation->save();
            }
        }
        return $saved;
    }

    protected function isTranslationDirty($translation)
    {
        $dirtyAttributes = $translation->getDirty();
        unset($dirtyAttributes[$this->getLocaleKey()]);
        return count($dirtyAttributes) > 0;
    }

    protected function getNewTranslationInstance($locale)
    {
        $modelName = $this->getTranslationModelName();
        /** @var Model $translation */
        $translation = new $modelName;
        $translation->setAttribute($this->getLocaleKey(), $locale);
        return $translation;
    }

    protected function performDeleteOnModel()
    {
        if ( ! $this->softDelete)
        {
            $this->deleteTranslations();
        }
        parent::performDeleteOnModel();
    }

    protected function deleteTranslations()
    {
        foreach ($this->translations as $translation)
        {
            $translation->delete();
        }
    }

}