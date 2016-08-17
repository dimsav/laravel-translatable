<?php

namespace Dimsav\Translatable;

use App;
use Dimsav\Translatable\Exception\LocalesNotDefinedException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

trait Translatable
{
    /**
     * Alias for getTranslation().
     *
     * @param string|null $locale
     * @param bool $withFallback
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function translate($locale = null, $withFallback = false)
    {
        return $this->getTranslation($locale, $withFallback);
    }

    /**
     * Alias for getTranslation().
     *
     * @param string $locale
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function translateOrDefault($locale)
    {
        return $this->getTranslation($locale, true);
    }

    /**
     * Alias for getTranslationOrNew().
     *
     * @param string $locale
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function translateOrNew($locale)
    {
        return $this->getTranslationOrNew($locale);
    }

    /**
     * @param string|null $locale
     * @param bool        $withFallback
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getTranslation($locale = null, $withFallback = null)
    {
        $configFallbackLocale = $this->getFallbackLocale($locale);
        $locale = $locale ?: $this->locale();
        $withFallback = $withFallback === null ? $this->useFallback() : $withFallback;
        $fallbackLocale = $this->getFallbackLocale($locale);

        if ($translation = $this->getTranslationByLocaleKey($locale)) {
            return $translation;
        }
        if ($withFallback && $fallbackLocale) {
            if ($translation = $this->getTranslationByLocaleKey($fallbackLocale)) {
                return $translation;
            }
            if ($translation = $this->getTranslationByLocaleKey($configFallbackLocale)) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * @param string|null $locale
     *
     * @return bool
     */
    public function hasTranslation($locale = null)
    {
        $locale = $locale ?: $this->locale();

        foreach ($this->translations as $translation) {
            if ($translation->getAttribute($this->getLocaleKey()) == $locale) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getTranslationModelName()
    {
        return $this->translationModel ?: $this->getTranslationModelNameDefault();
    }

    /**
     * @return string
     */
    public function getTranslationModelNameDefault()
    {
        $config = app()->make('config');

        return get_class($this).$config->get('translatable.translation_suffix', 'Translation');
    }

    /**
     * @return string
     */
    public function getRelationKey()
    {
        if ($this->translationForeignKey) {
            $key = $this->translationForeignKey;
        } elseif ($this->primaryKey !== 'id') {
            $key = $this->primaryKey;
        } else {
            $key = $this->getForeignKey();
        }

        return $key;
    }

    /**
     * @return string
     */
    public function getLocaleKey()
    {
        $config = app()->make('config');

        return $this->localeKey ?: $config->get('translatable.locale_key', 'locale');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        return $this->hasMany($this->getTranslationModelName(), $this->getRelationKey());
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (str_contains($key, ':')) {
            list($key, $locale) = explode(':', $key);
        } else {
            $locale = $this->locale();
        }

        if ($this->isTranslationAttribute($key)) {
            if ($this->getTranslation($locale) === null) {
                return null;
            }

            // If the given $key has a mutator, we push it to $attributes and then call getAttributeValue
            // on it. This way, we can use Eloquent's checking for Mutation, type casting, and
            // Date fields.
            if ($this->hasGetMutator($key)) {
                $this->attributes[$key] = $this->getTranslation($locale)->$key;

                return $this->getAttributeValue($key);
            }

            return $this->getTranslation($locale)->$key;
        }

        return parent::getAttribute($key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setAttribute($key, $value)
    {
        if (str_contains($key, ':')) {
            list($key, $locale) = explode(':', $key);
        } else {
            $locale = $this->locale();
        }

        if ($this->isTranslationAttribute($key)) {
            $this->getTranslationOrNew($locale)->$key = $value;
        } else {
            return parent::setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            if (count($this->getDirty()) > 0) {
                // If $this->exists and dirty, parent::save() has to return true. If not,
                // an error has occurred. Therefore we shouldn't save the translations.
                if (parent::save($options)) {
                    return $this->saveTranslations();
                }

                return false;
            } else {
                // If $this->exists and not dirty, parent::save() skips saving and returns
                // false. So we have to save the translations
                if ($saved = $this->saveTranslations()) {
                    $this->fireModelEvent('saved', false);
                    $this->fireModelEvent('updated', false);
                }

                return $saved;
            }
        } elseif (parent::save($options)) {
            // We save the translations only if the instance is saved in the database.
            return $this->saveTranslations();
        }

        return false;
    }

    /**
     * @param string $locale
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getTranslationOrNew($locale)
    {
        if (($translation = $this->getTranslation($locale, false)) === null) {
            $translation = $this->getNewTranslation($locale);
        }

        return $translation;
    }

    /**
     * @param array $attributes
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     *
     * @return $this
     */
    public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();

        foreach ($attributes as $key => $values) {
            if ($this->isKeyALocale($key)) {
                foreach ($values as $translationAttribute => $translationValue) {
                    if ($this->alwaysFillable() || $this->isFillable($translationAttribute)) {
                        $this->getTranslationOrNew($key)->$translationAttribute = $translationValue;
                    } elseif ($totallyGuarded) {
                        throw new MassAssignmentException($key);
                    }
                }
                unset($attributes[$key]);
            }
        }

        return parent::fill($attributes);
    }

    /**
     * @param string $key
     */
    private function getTranslationByLocaleKey($key)
    {
        foreach ($this->translations as $translation) {
            if ($translation->getAttribute($this->getLocaleKey()) == $key) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * @param null $locale
     *
     * @return string
     */
    private function getFallbackLocale($locale = null)
    {
        if ($locale && $this->isLocaleCountryBased($locale)) {
            if ($fallback = $this->getLanguageFromCountryBasedLocale($locale)) {
                return $fallback;
            }
        }

        return app()->make('config')->get('translatable.fallback_locale');
    }

    /**
     * @param $locale
     *
     * @return bool
     */
    private function isLocaleCountryBased($locale)
    {
        return strpos($locale, $this->getLocaleSeparator()) !== false;
    }

    /**
     * @param $locale
     *
     * @return string
     */
    private function getLanguageFromCountryBasedLocale($locale)
    {
        $parts = explode($this->getLocaleSeparator(), $locale);

        return array_get($parts, 0);
    }

    /**
     * @return bool|null
     */
    private function useFallback()
    {
        if (isset($this->useTranslationFallback) && $this->useTranslationFallback !== null) {
            return $this->useTranslationFallback;
        }

        return app()->make('config')->get('translatable.use_fallback');
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isTranslationAttribute($key)
    {
        return in_array($key, $this->translatedAttributes);
    }

    /**
     * @param string $key
     *
     * @throws \Dimsav\Translatable\Exception\LocalesNotDefinedException
     *
     * @return bool
     */
    protected function isKeyALocale($key)
    {
        $locales = $this->getLocales();

        return in_array($key, $locales);
    }

    /**
     * @throws \Dimsav\Translatable\Exception\LocalesNotDefinedException
     *
     * @return array
     */
    protected function getLocales()
    {
        $localesConfig = (array) app()->make('config')->get('translatable.locales');

        if (empty($localesConfig)) {
            throw new LocalesNotDefinedException('Please make sure you have run "php artisan config:publish dimsav/laravel-translatable" '.
                ' and that the locales configuration is defined.');
        }

        $locales = [];
        foreach ($localesConfig as $key => $locale) {
            if (is_array($locale)) {
                $locales[] = $key;
                foreach ($locale as $countryLocale) {
                    $locales[] = $key.$this->getLocaleSeparator().$countryLocale;
                }
            } else {
                $locales[] = $locale;
            }
        }

        return $locales;
    }

    /**
     * @return string
     */
    protected function getLocaleSeparator()
    {
        return app()->make('config')->get('translatable.locale_separator', '-');
    }

    /**
     * @return bool
     */
    protected function saveTranslations()
    {
        $saved = true;
        foreach ($this->translations as $translation) {
            if ($saved && $this->isTranslationDirty($translation)) {
                $translation->setAttribute($this->getRelationKey(), $this->getKey());
                $saved = $translation->save();
            }
        }

        return $saved;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $translation
     *
     * @return bool
     */
    protected function isTranslationDirty(Model $translation)
    {
        $dirtyAttributes = $translation->getDirty();
        unset($dirtyAttributes[$this->getLocaleKey()]);

        return count($dirtyAttributes) > 0;
    }

    /**
     * @param string $locale
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getNewTranslation($locale)
    {
        $modelName = $this->getTranslationModelName();
        $translation = new $modelName();
        $translation->setAttribute($this->getLocaleKey(), $locale);
        $this->translations->add($translation);

        return $translation;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return $this->isTranslationAttribute($key) || parent::__isset($key);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $locale
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeTranslatedIn(Builder $query, $locale = null)
    {
        $locale = $locale ?: $this->locale();

        return $query->whereHas('translations', function (Builder $q) use ($locale) {
            $q->where($this->getLocaleKey(), '=', $locale);
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $locale
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeNotTranslatedIn(Builder $query, $locale = null)
    {
        $locale = $locale ?: $this->locale();

        return $query->whereDoesntHave('translations', function (Builder $q) use ($locale) {
            $q->where($this->getLocaleKey(), '=', $locale);
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeTranslated(Builder $query)
    {
        return $query->has('translations');
    }

    /**
     * Adds scope to get a list of translated attributes, using the current locale.
     *
     * Example usage: Country::listsTranslations('name')->get()->toArray()
     * Will return an array with items:
     *  [
     *      'id' => '1',                // The id of country
     *      'name' => 'Griechenland'    // The translated name
     *  ]
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $translationField
     */
    public function scopeListsTranslations(Builder $query, $translationField)
    {
        $withFallback = $this->useFallback();
        $translationTable = $this->getTranslationsTable();
        $localeKey = $this->getLocaleKey();

        $query
            ->select($this->getTable().'.'.$this->getKeyName(), $translationTable.'.'.$translationField)
            ->leftJoin($translationTable, $translationTable.'.'.$this->getRelationKey(), '=', $this->getTable().'.'.$this->getKeyName())
            ->where($translationTable.'.'.$localeKey, $this->locale());
        if ($withFallback) {
            $query->orWhere(function (Builder $q) use ($translationTable, $localeKey) {
                $q->where($translationTable.'.'.$localeKey, $this->getFallbackLocale())
                  ->whereNotIn($translationTable.'.'.$this->getRelationKey(), function (QueryBuilder $q) use ($translationTable, $localeKey) {
                      $q->select($translationTable.'.'.$this->getRelationKey())
                        ->from($translationTable)
                        ->where($translationTable.'.'.$localeKey, $this->locale());
                  });
            });
        }
    }

    /**
     * This scope eager loads the translations for the default and the fallback locale only.
     * We can use this as a shortcut to improve performance in our application.
     *
     * @param Builder $query
     */
    public function scopeWithTranslation(Builder $query)
    {
        $query->with(['translations' => function (Relation $query) {
            $query->where($this->getTranslationsTable().'.'.$this->getLocaleKey(), $this->locale());

            if ($this->useFallback()) {
                return $query->orWhere($this->getTranslationsTable().'.'.$this->getLocaleKey(), $this->getFallbackLocale());
            }
        }]);
    }

    /**
     * This scope filters results by checking the translation fields.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $key
     * @param string                                $value
     * @param string                                $locale
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeWhereTranslation(Builder $query, $key, $value, $locale = null)
    {
        return $query->whereHas('translations', function (Builder $query) use ($key, $value, $locale) {
            $query->where($this->getTranslationsTable().'.'.$key, $value);
            if ($locale) {
                $query->where($this->getTranslationsTable().'.'.$this->getLocaleKey(), $locale);
            }
        });
    }

    /**
     * This scope filters results by checking the translation fields.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $key
     * @param string                                $value
     * @param string                                $locale
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeWhereTranslationLike(Builder $query, $key, $value, $locale = null)
    {
        return $query->whereHas('translations', function (Builder $query) use ($key, $value, $locale) {
            $query->where($this->getTranslationsTable().'.'.$key, 'LIKE', $value);
            if ($locale) {
                $query->where($this->getTranslationsTable().'.'.$this->getLocaleKey(), 'LIKE', $locale);
            }
        });
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $attributes = parent::toArray();

        $hiddenAttributes = $this->getHidden();

        foreach ($this->translatedAttributes as $field) {
            if (in_array($field, $hiddenAttributes)) {
                continue;
            }

            if ($translations = $this->getTranslation()) {
                $attributes[$field] = $translations->$field;
            }
        }

        return $attributes;
    }

    /**
     * @return bool
     */
    private function alwaysFillable()
    {
        return app()->make('config')->get('translatable.always_fillable', false);
    }

    /**
     * @return string
     */
    private function getTranslationsTable()
    {
        return app()->make($this->getTranslationModelName())->getTable();
    }

    /**
     * @return string
     */
    protected function locale()
    {
        return app()->make('config')->get('translatable.locale')
            ?: app()->make('translator')->getLocale();
    }
}
