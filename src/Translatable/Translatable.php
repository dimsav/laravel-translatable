<?php namespace Dimsav\Translatable;

use App;
use Dimsav\Translatable\Exception\LocalesNotDefinedException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;

trait Translatable {

    /*
     * Alias for getTranslation()
     */
    public function translate($locale = null, $withFallback = false)
    {
        return $this->getTranslation($locale, $withFallback);
    }

    /*
     * Alias for getTranslation()
     */
    public function translateOrDefault($locale)
    {
        return $this->getTranslation($locale, true);
    }

    /*
     * Alias for getTranslationOrNew()
     */
    public function translateOrNew($locale)
    {
        return $this->getTranslationOrNew($locale);
    }

    /**
     * @param null $locale
     * @param bool|null $withFallback
     * @return Model|null
     */
    public function getTranslation($locale = null, $withFallback = null)
    {
        $locale = $locale ?: App::getLocale();

        if ($withFallback === null)
        {
            $withFallback = isset($this->useTranslationFallback) ? $this->useTranslationFallback : false;
        }

        if ($this->getTranslationByLocaleKey($locale))
        {
            $translation = $this->getTranslationByLocaleKey($locale);
        }
        elseif ($withFallback
            && App::make('config')->get('translatable::fallback_locale')
            && $this->getTranslationByLocaleKey(App::make('config')->get('translatable::fallback_locale'))
        )
        {
            $translation = $this->getTranslationByLocaleKey(App::make('config')->get('translatable::fallback_locale'));
        }
        else
        {
            $translation = null;
        }

        return $translation;
    }

    public function hasTranslation($locale = null)
    {
        $locale = $locale ?: App::getLocale();

        foreach ($this->translations as $translation)
        {
            if ($translation->getAttribute($this->getLocaleKey()) == $locale)
            {
                return true;
            }
        }

        return false;
    }

    public function getTranslationModelName()
    {
        return $this->translationModel ?: $this->getTranslationModelNameDefault();
    }

    public function getTranslationModelNameDefault()
    {
        $config = App::make('config');

        return get_class($this) . $config->get('translatable::translation_suffix', 'Translation');
    }

    public function getRelationKey()
    {
        return $this->translationForeignKey ?: $this->getForeignKey();
    }

    public function getLocaleKey()
    {
        $config = App::make('config');
        return $this->localeKey ?: $config->get('translatable::locale_key', 'locale');
    }

    public function translations()
    {
        return $this->hasMany($this->getTranslationModelName(), $this->getRelationKey());
    }

    public function getAttribute($key)
    {
        if ($this->isKeyReturningTranslationText($key))
        {
            if ($this->getTranslation() === null)
            {
                return null;
            }
            return $this->getTranslation()->$key;
        }
        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->translatedAttributes))
        {
            $this->getTranslationOrNew(App::getLocale())->$key = $value;
        }
        else
        {
            parent::setAttribute($key, $value);
        }
    }

    public function save(array $options = array())
    {
        if ($this->exists)
        {
            if (count($this->getDirty()) > 0)
            {
                // If $this->exists and dirty, parent::save() has to return true. If not,
                // an error has occurred. Therefore we shouldn't save the translations.
                if (parent::save($options))
                {
                    return $this->saveTranslations();
                }
                return false;
            }
            else
            {
                // If $this->exists and not dirty, parent::save() skips saving and returns
                // false. So we have to save the translations
                if($saved = $this->saveTranslations())
                {
	                $this->fireModelEvent('saved', false);
                }
                
                return $saved;
            }
        }
        elseif (parent::save($options))
        {
            // We save the translations only if the instance is saved in the database.
            return $this->saveTranslations();
        }
        return false;
    }

    protected function getTranslationOrNew($locale)
    {
        if (($translation = $this->getTranslation($locale, false)) === null)
        {
            $translation = $this->getNewTranslation($locale);
        }
        return $translation;
    }

    public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();

        foreach ($attributes as $key => $values)
        {
            if ($this->isKeyALocale($key))
            {
                foreach ($values as $translationAttribute => $translationValue)
                {
                    if ($this->alwaysFillable() or $this->isFillable($translationAttribute))
                    {
                        $this->getTranslationOrNew($key)->$translationAttribute = $translationValue;
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

    private function getTranslationByLocaleKey($key)
    {
        foreach ($this->translations as $translation)
        {
            if ($translation->getAttribute($this->getLocaleKey()) == $key)
            {
                return $translation;
            }
        }
        return null;
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
        $config = App::make('config');
        $locales = (array) $config->get('translatable::locales', array());

        if (empty($locales))
        {
            throw new LocalesNotDefinedException('Please make sure you have run "php artisan config:publish dimsav/laravel-translatable" '.
                ' and that the locales configuration is defined.');
        }
        return $locales;
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

    protected function isTranslationDirty(Model $translation)
    {
        $dirtyAttributes = $translation->getDirty();
        unset($dirtyAttributes[$this->getLocaleKey()]);
        return count($dirtyAttributes) > 0;
    }

    public function getNewTranslation($locale)
    {
        $modelName = $this->getTranslationModelName();
        $translation = new $modelName;
        $translation->setAttribute($this->getLocaleKey(), $locale);
        $this->translations->add($translation);
        return $translation;
    }

    public function __isset($key)
    {
        return (in_array($key, $this->translatedAttributes) || parent::__isset($key));
    }

    public function scopeTranslatedIn(Builder $query, $locale)
    {
        return $query->whereHas('translations', function(Builder $q) use ($locale)
        {
            $q->where($this->getLocaleKey(), '=', $locale);
        });
    }

    public function scopeTranslated(Builder $query)
    {
        return $query->has('translations');
    }

    public function toArray()
    {
        $attributes = parent::toArray();

        foreach($this->translatedAttributes AS $field)
        {
            if ($translations = $this->getTranslation())
            {
                $attributes[$field] = $translations->$field;
            }
        }

        return $attributes;
    }

    private function alwaysFillable()
    {
        return App::make('config')->get('translatable::always_fillable', false);
    }

}
