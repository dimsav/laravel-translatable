<?php

namespace Dimsav\Translatable;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Dimsav\Translatable\Exception\LocalesNotDefinedException;

/**
 * @property-read Collection|Model[] $translations
 * @property-read string $translationModel
 * @property-read string $translationForeignKey
 * @property-read string $localeKey
 * @property-read bool $useTranslationFallback
 *
 * @mixin Model
 */
trait Translatable
{
    protected static $autoloadTranslations = null;

    protected $defaultLocale;

    public function __isset($key): bool
    {
        return $this->isTranslationAttribute($key) || parent::__isset($key);
    }

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

    public static function defaultAutoloadTranslations(): void
    {
        self::$autoloadTranslations = null;
    }

    public function deleteTranslations($locales = null)
    {
        if ($locales === null) {
            $translations = $this->translations()->get();
        } else {
            $translations = $this->translations()->whereIn($this->getLocaleKey(), (array) $locales)->get();
        }

        foreach ($translations as $translation) {
            $translation->delete();
        }

        // we need to manually "reload" the collection built from the relationship
        // otherwise $this->translations()->get() would NOT be the same as $this->translations
        $this->load('translations');
    }

    public static function disableAutoloadTranslations(): void
    {
        self::$autoloadTranslations = false;
    }

    public static function enableAutoloadTranslations(): void
    {
        self::$autoloadTranslations = true;
    }

    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $values) {
            if ($this->isKeyALocale($key)) {
                $this->getTranslationOrNew($key)->fill($values);
                unset($attributes[$key]);
            } else {
                list($attribute, $locale) = $this->getAttributeAndLocale($key);
                if ($this->isTranslationAttribute($attribute) and $this->isKeyALocale($locale)) {
                    $this->getTranslationOrNew($locale)->fill([$attribute => $values]);
                    unset($attributes[$key]);
                }
            }
        }

        return parent::fill($attributes);
    }

    public function getAttribute($key)
    {
        list($attribute, $locale) = $this->getAttributeAndLocale($key);

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

    public function getDefaultLocale(): ?string
    {
        return $this->defaultLocale;
    }

    public function getLocaleKey(): string
    {
        return $this->localeKey ?: config('translatable.locale_key', 'locale');
    }

    public function getNewTranslation(?string $locale): Model
    {
        $modelName = $this->getTranslationModelName();
        $translation = new $modelName();
        $translation->setAttribute($this->getLocaleKey(), $locale);
        $this->translations->add($translation);

        return $translation;
    }

    public function getRelationKey(): string
    {
        if ($this->translationForeignKey) {
            return $this->translationForeignKey;
        } elseif ($this->primaryKey !== 'id') {
            return $this->primaryKey;
        }

        return $this->getForeignKey();
    }

    public function getTranslation(?string $locale = null, bool $withFallback = null): ?Model
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

    public function getTranslationModelName(): string
    {
        return $this->translationModel ?: $this->getTranslationModelNameDefault();
    }

    public function getTranslationModelNameDefault(): string
    {
        $modelName = get_class($this);

        if ($namespace = $this->getTranslationModelNamespace()) {
            $modelName = $namespace.'\\'.class_basename(get_class($this));
        }

        return $modelName.config('translatable.translation_suffix', 'Translation');
    }

    public function getTranslationModelNamespace(): ?string
    {
        return config('translatable.translation_model_namespace');
    }

    public function getTranslationsArray(): array
    {
        $translations = [];

        foreach ($this->translations as $translation) {
            foreach ($this->translatedAttributes as $attr) {
                $translations[$translation->{$this->getLocaleKey()}][$attr] = $translation->{$attr};
            }
        }

        return $translations;
    }

    public function hasTranslation(?string $locale = null): bool
    {
        $locale = $locale ?: $this->locale();

        foreach ($this->translations as $translation) {
            if ($translation->getAttribute($this->getLocaleKey()) == $locale) {
                return true;
            }
        }

        return false;
    }

    public function isTranslationAttribute(string $key): bool
    {
        return in_array($key, $this->translatedAttributes);
    }

    public function replicateWithTranslations(array $except = null): Model
    {
        $newInstance = parent::replicate($except);

        unset($newInstance->translations);
        foreach ($this->translations as $translation) {
            $newTranslation = $translation->replicate();
            $newInstance->translations->add($newTranslation);
        }

        return $newInstance;
    }

    public function save(array $options = []): bool
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

    public function scopeListsTranslations(Builder $query, string $translationField): Builder
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

        return $query;
    }

    public function scopeNotTranslatedIn(Builder $query, ?string $locale = null): Builder
    {
        $locale = $locale ?: $this->locale();

        return $query->whereDoesntHave('translations', function (Builder $q) use ($locale) {
            $q->where($this->getLocaleKey(), '=', $locale);
        });
    }

    public function scopeOrderByTranslation(Builder $query, string $key, string $sortMethod = 'asc'): Builder
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
            ->orderBy($translationTable.'.'.$key, $sortMethod)
            ->select($table.'.*')
            ->with('translations');
    }

    public function scopeOrWhereTranslation(Builder $query, string $key, $value, ?string $locale = null)
    {
        return $this->scopeWhereTranslation($query, $key, $value, $locale, true);
    }

    public function scopeOrWhereTranslationLike(Builder $query, string $key, $value, ?string $locale = null): Builder
    {
        return $this->scopeWhereTranslation($query, $key, $value, $locale, true, 'LIKE');
    }

    public function scopeTranslated(Builder $query): Builder
    {
        return $query->has('translations');
    }

    public function scopeTranslatedIn(Builder $query, ?string $locale = null): Builder
    {
        $locale = $locale ?: $this->locale();

        return $query->whereHas('translations', function (Builder $q) use ($locale) {
            $q->where($this->getLocaleKey(), '=', $locale);
        });
    }

    public function scopeWhereTranslation(Builder $query, string $key, $value, ?string $locale = null, bool $or = false, string $operator = '=')
    {
        return $query->{$or ? 'orWhereHas' : 'whereHas'}('translations', function (Builder $query) use ($key, $operator, $value, $locale) {
            $query->where($this->getTranslationsTable().'.'.$key, $operator, $value);
            if ($locale) {
                $query->where($this->getTranslationsTable().'.'.$this->getLocaleKey(), $operator, $locale); // ToDo: BC - the locale should be an equal comparison
            }
        });
    }

    public function scopeWhereTranslationLike(Builder $query, string $key, $value, ?string $locale = null): Builder
    {
        return $this->scopeWhereTranslation($query, $key, $value, $locale, false, 'LIKE');
    }

    public function scopeWithTranslation(Builder $query): Builder
    {
        return $query->with([
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

    public function setAttribute($key, $value)
    {
        list($attribute, $locale) = $this->getAttributeAndLocale($key);

        if ($this->isTranslationAttribute($attribute)) {
            $this->getTranslationOrNew($locale)->$attribute = $value;
        } else {
            return parent::setAttribute($key, $value);
        }

        return $this;
    }

    public function setDefaultLocale(?string $locale)
    {
        $this->defaultLocale = $locale;

        return $this;
    }

    public function translate(?string $locale = null, bool $withFallback = false): ?Model
    {
        return $this->getTranslation($locale, $withFallback);
    }

    public function translateOrDefault(?string $locale = null): ?Model
    {
        return $this->getTranslation($locale, true);
    }

    public function translateOrNew(?string $locale = null): Model
    {
        return $this->getTranslationOrNew($locale);
    }

    public function translations(): HasMany
    {
        return $this->hasMany($this->getTranslationModelName(), $this->getRelationKey());
    }

    protected function getLocales(): array
    {
        $localesConfig = (array) config('translatable.locales', []);

        if (empty($localesConfig)) {
            throw new LocalesNotDefinedException('Please make sure you have run "php artisan config:publish dimsav/laravel-translatable" and that the locales configuration is defined.');
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

    protected function getLocaleSeparator(): string
    {
        return config('translatable.locale_separator', '-');
    }

    protected function getTranslationOrNew(?string $locale = null): Model
    {
        $locale = $locale ?: $this->locale();

        if (($translation = $this->getTranslation($locale, false)) === null) {
            $translation = $this->getNewTranslation($locale);
        }

        return $translation;
    }

    protected function isKeyALocale(string $key): bool
    {
        $locales = $this->getLocales();

        return in_array($key, $locales);
    }

    protected function isTranslationDirty(Model $translation): bool
    {
        $dirtyAttributes = $translation->getDirty();
        unset($dirtyAttributes[$this->getLocaleKey()]);

        return ! empty($dirtyAttributes);
    }

    protected function locale(): string
    {
        if ($this->defaultLocale) {
            return $this->defaultLocale;
        }

        return config('translatable.locale')
            ?: app()->make('translator')->getLocale();
    }

    protected function saveTranslations(): bool
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

    private function getAttributeAndLocale(string $key): array
    {
        if (Str::contains($key, ':')) {
            return explode(':', $key);
        }

        return [$key, $this->locale()];
    }

    private function getAttributeOrFallback(?string $locale, string $attribute)
    {
        $translation = $this->getTranslation($locale); // ToDo: BC - do not use double fallback

        if (
            (
                ! $translation instanceof Model
                || empty($translation->getAttribute($attribute))
            )
            && $this->usePropertyFallback()
        ) {
            $translation = $this->getTranslation($this->getFallbackLocale(), false);
        }

        if ($translation instanceof Model) {
            return $translation->getAttribute($attribute);
        }

        return null;
    }

    private function getFallbackLocale(?string $locale = null): string
    {
        if ($locale && $this->isLocaleCountryBased($locale)) {
            if ($fallback = $this->getLanguageFromCountryBasedLocale($locale)) {
                return $fallback;
            }
        }

        return config('translatable.fallback_locale');
    }

    private function getLanguageFromCountryBasedLocale(string $locale): string
    {
        $parts = explode($this->getLocaleSeparator(), $locale);

        return Arr::get($parts, 0);
    }

    private function getTranslationByLocaleKey(string $key): ?Model
    {
        foreach ($this->translations as $translation) {
            if ($translation->getAttribute($this->getLocaleKey()) == $key) {
                return $translation;
            }
        }

        return null;
    }

    private function getTranslationsTable(): string
    {
        return app()->make($this->getTranslationModelName())->getTable();
    }

    private function isLocaleCountryBased(?string $locale): bool
    {
        return strpos($locale, $this->getLocaleSeparator()) !== false;
    }

    private function toArrayAlwaysLoadsTranslations(): bool
    {
        return config('translatable.to_array_always_loads_translations', true);
    }

    private function useFallback(): bool
    {
        if (isset($this->useTranslationFallback) && $this->useTranslationFallback !== null) {
            return $this->useTranslationFallback;
        }

        return config('translatable.use_fallback');
    }

    private function usePropertyFallback(): bool
    {
        return $this->useFallback() && config('translatable.use_property_fallback', false);
    }
}
