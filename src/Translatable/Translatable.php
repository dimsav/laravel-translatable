<?php

namespace Dimsav\Translatable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Dimsav\Translatable\Exception\LocalesNotDefinedException;

trait Translatable
{
    protected static $autoloadTranslations = null;

    protected $defaultLocale;

    /**
     * Alias for getTranslation().
     *
     * @param string|null $locale
     * @param bool        $withFallback
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
    public function translateOrDefault($locale = null)
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
    public function translateOrNew($locale = null)
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
        $configFallbackLocale = $this->getFallbackLocale();
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
            if ($fallbackLocale !== $configFallbackLocale && $translation = $this->getTranslationByLocaleKey($configFallbackLocale)) {
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
        $modelName = get_class($this);

        if ($namespace = $this->getTranslationModelNamespace()) {
            $modelName = $namespace.'\\'.class_basename(get_class($this));
        }

        return $modelName.config('translatable.translation_suffix', 'Translation');
    }

    /**
     * @return string|null
     */
    public function getTranslationModelNamespace()
    {
        return config('translatable.translation_model_namespace');
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
        return $this->localeKey ?: config('translatable.locale_key', 'locale');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        return $this->hasMany($this->getTranslationModelName(), $this->getRelationKey());
    }

    /**
     * @return bool
     */
    private function usePropertyFallback()
    {
        return $this->useFallback() && config('translatable.use_property_fallback', false);
    }

    /**
     * Returns the attribute value from fallback translation if value of attribute
     * is empty and the property fallback is enabled in the configuration.
     * in model.
     * @param $locale
     * @param $attribute
     * @return mixed
     */
    private function getAttributeOrFallback($locale, $attribute)
    {
        $translation = $this->getTranslation($locale);

        if (
            (
                ! $translation instanceof Model ||
                $this->isEmptyTranslatableAttribute($attribute, $translation->$attribute)
            ) &&
            $this->usePropertyFallback()
        ) {
            $translation = $this->getTranslation($this->getFallbackLocale(), false);
        }

        if ($translation instanceof Model) {
            return $translation->$attribute;
        }

        return null;
    }

    protected function isEmptyTranslatableAttribute(string $key, $value): bool
    {
        return empty($value);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getAttribute($key)
    {
        [$attribute, $locale] = $this->getAttributeAndLocale($key);

        if ($this->isTranslationAttribute($attribute)) {
            if ($this->getTranslation($locale) === null) {
                return $this->getAttributeValue($attribute);
            }

            // If the given $attribute has a mutator, we push it to $attributes and then call getAttributeValue
            // on it. This way, we can use Eloquent's checking for Mutation, type casting, and
            // Date fields.
            if ($this->hasGetMutator($attribute)) {
                $this->attributes[$attribute] = $this->getAttributeOrFallback($locale, $attribute);

                return $this->getAttributeValue($attribute);
            }

            return $this->getAttributeOrFallback($locale, $attribute);
        }

        return parent::getAttribute($key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        [$attribute, $locale] = $this->getAttributeAndLocale($key);

        if ($this->isTranslationAttribute($attribute)) {
            $this->getTranslationOrNew($locale)->$attribute = $value;
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
        if ($this->exists && ! $this->isDirty()) {
            // If $this->exists and not dirty, parent::save() skips saving and returns
            // false. So we have to save the translations
            if ($this->fireModelEvent('saving') === false) {
                return false;
            }

            if ($saved = $this->saveTranslations()) {
                $this->fireModelEvent('saved', false);
                $this->fireModelEvent('updated', false);
            }

            return $saved;
        }

        // We save the translations only if the instance is saved in the database.
        if (parent::save($options)) {
            return $this->saveTranslations();
        }

        return false;
    }

    /**
     * @param string $locale
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getTranslationOrNew($locale = null)
    {
        $locale = $locale ?: $this->locale();

        if (($translation = $this->getTranslation($locale, false)) === null) {
            $translation = $this->getNewTranslation($locale);
        }

        return $translation;
    }

    /**
     * @param array $attributes
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     * @return $this
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $values) {
            if ($this->isKeyALocale($key)) {
                $this->getTranslationOrNew($key)->fill($values);
                unset($attributes[$key]);
            } else {
                [$attribute, $locale] = $this->getAttributeAndLocale($key);
                if ($this->isTranslationAttribute($attribute) and $this->isKeyALocale($locale)) {
                    $this->getTranslationOrNew($locale)->fill([$attribute => $values]);
                    unset($attributes[$key]);
                }
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

        return config('translatable.fallback_locale');
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

        return config('translatable.use_fallback');
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
     * @return bool
     */
    protected function isKeyALocale($key)
    {
        $locales = $this->getLocales();

        return in_array($key, $locales);
    }

    /**
     * @throws \Dimsav\Translatable\Exception\LocalesNotDefinedException
     * @return array
     */
    protected function getLocales()
    {
        $localesConfig = (array) config('translatable.locales');

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
        return config('translatable.locale_separator', '-');
    }

    /**
     * @return bool
     */
    protected function saveTranslations()
    {
        $saved = true;

        if (! $this->relationLoaded('translations')) {
            return $saved;
        }

        foreach ($this->translations as $translation) {
            if ($saved && $this->isTranslationDirty($translation)) {
                if (! empty($connectionName = $this->getConnectionName())) {
                    $translation->setConnection($connectionName);
                }

                $translation->setAttribute($this->getRelationKey(), $this->getKey());
                $saved = $translation->save();
            }
        }

        return $saved;
    }

    /**
     * @param array
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function replicateWithTranslations(array $except = null)
    {
        $newInstance = parent::replicate($except);

        unset($newInstance->translations);
        foreach ($this->translations as $translation) {
            $newTranslation = $translation->replicate();
            $newInstance->translations->add($newTranslation);
        }

        return  $newInstance;
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
     * Example usage: Country::listsTranslations('name')->get()->toArray()
     * Will return an array with items:
     *  [
     *      'id' => '1',                // The id of country
     *      'name' => 'Griechenland'    // The translated name
     *  ].
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
                  ->whereNotIn($translationTable.'.'.$this->getRelationKey(), function (QueryBuilder $q) use (
                      $translationTable,
                      $localeKey
                  ) {
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
        $query->with([
            'translations' => function (Relation $query) {
                if ($this->useFallback()) {
                    $locale = $this->locale();
                    $countryFallbackLocale = $this->getFallbackLocale($locale); // e.g. de-DE => de
                    $locales = array_unique([$locale, $countryFallbackLocale, $this->getFallbackLocale()]);

                    return $query->whereIn($this->getTranslationsTable().'.'.$this->getLocaleKey(), $locales);
                }

                return $query->where($this->getTranslationsTable().'.'.$this->getLocaleKey(), $this->locale());
            },
        ]);
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
    public function scopeOrWhereTranslation(Builder $query, $key, $value, $locale = null)
    {
        return $query->orWhereHas('translations', function (Builder $query) use ($key, $value, $locale) {
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
     * This scope filters results by checking the translation fields.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $key
     * @param string                                $value
     * @param string                                $locale
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeOrWhereTranslationLike(Builder $query, $key, $value, $locale = null)
    {
        return $query->orWhereHas('translations', function (Builder $query) use ($key, $value, $locale) {
            $query->where($this->getTranslationsTable().'.'.$key, 'LIKE', $value);
            if ($locale) {
                $query->where($this->getTranslationsTable().'.'.$this->getLocaleKey(), 'LIKE', $locale);
            }
        });
    }

    /**
     * This scope sorts results by the given translation field.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $key
     * @param string                                $sortmethod
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeOrderByTranslation(Builder $query, $key, $sortmethod = 'asc')
    {
        $translationTable = $this->getTranslationsTable();
        $localeKey = $this->getLocaleKey();
        $table = $this->getTable();
        $keyName = $this->getKeyName();

        return $query
            ->join($translationTable, function (JoinClause $join) use ($translationTable, $localeKey, $table, $keyName) {
                $join
                    ->on($translationTable.'.'.$this->getRelationKey(), '=', $table.'.'.$keyName)
                    ->where($translationTable.'.'.$localeKey, $this->locale());
            })
            ->orderBy($translationTable.'.'.$key, $sortmethod)
            ->select($table.'.*')
            ->with('translations');
    }

    /**
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        if (
            (! $this->relationLoaded('translations') && ! $this->toArrayAlwaysLoadsTranslations() && is_null(self::$autoloadTranslations))
            || self::$autoloadTranslations === false
        ) {
            return $attributes;
        }

        $hiddenAttributes = $this->getHidden();

        foreach ($this->translatedAttributes as $field) {
            if (in_array($field, $hiddenAttributes)) {
                continue;
            }

            $attributes[$field] = $this->getAttributeOrFallback(null, $field);
        }

        return $attributes;
    }

    /**
     * @return array
     */
    public function getTranslationsArray()
    {
        $translations = [];

        foreach ($this->translations as $translation) {
            foreach ($this->translatedAttributes as $attr) {
                $translations[$translation->{$this->getLocaleKey()}][$attr] = $translation->{$attr};
            }
        }

        return $translations;
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
        if ($this->defaultLocale) {
            return $this->defaultLocale;
        }

        return config('translatable.locale')
            ?: app()->make('translator')->getLocale();
    }

    /**
     * Set the default locale on the model.
     *
     * @param $locale
     *
     * @return $this
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;

        return $this;
    }

    /**
     * Get the default locale on the model.
     *
     * @return mixed
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Deletes all translations for this model.
     *
     * @param string|array|null $locales The locales to be deleted (array or single string)
     *                                   (e.g., ["en", "de"] would remove these translations).
     */
    public function deleteTranslations($locales = null)
    {
        if ($locales === null) {
            $translations = $this->translations()->get();
        } else {
            $locales = (array) $locales;
            $translations = $this->translations()->whereIn($this->getLocaleKey(), $locales)->get();
        }
        foreach ($translations as $translation) {
            $translation->delete();
        }

        // we need to manually "reload" the collection built from the relationship
        // otherwise $this->translations()->get() would NOT be the same as $this->translations
        $this->load('translations');
    }

    /**
     * @param $key
     *
     * @return array
     */
    private function getAttributeAndLocale($key)
    {
        if (str_contains($key, ':')) {
            return explode(':', $key);
        }

        return [$key, $this->locale()];
    }

    /**
     * @return bool
     */
    private function toArrayAlwaysLoadsTranslations()
    {
        return config('translatable.to_array_always_loads_translations', true);
    }

    public static function enableAutoloadTranslations()
    {
        self::$autoloadTranslations = true;
    }

    public static function defaultAutoloadTranslations()
    {
        self::$autoloadTranslations = null;
    }

    public static function disableAutoloadTranslations()
    {
        self::$autoloadTranslations = false;
    }
}
