<?php namespace Dimsav\Translatable;

use App;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Translatable
 * @package Dimsav\Translatable
 */
trait Translatable {

	/**
	 * Force locale for form generation for example
	 * 
	 * @var null|string
	 */
	public $forcedLocale = null;

	/**
	 * Alias for getTranslation()
	 *
	 * @param null $locale
	 * @param bool $withFallback
	 * @return mixed|null
	 */
    public function translate($locale = null, $withFallback = false)
    {
        return $this->getTranslation($locale, $withFallback);
    }

	/**
	 *  Alias for getTranslation()
	 *
	 * @param $locale
	 * @return mixed|null
	 */
    public function translateOrDefault($locale)
    {
        return $this->getTranslation($locale, true);
    }

	/**
	 * Get translation
	 *
	 * @param null $locale
	 * @param null $withFallback
	 * @return mixed|null
	 */
    public function getTranslation($locale = null, $withFallback = null)
    {
        $locale = $locale ?: $this->getLocale();

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
            $translation = $this->getNewTranslationInstance($locale);
            $this->translations->add($translation);
        }

        return $translation;
    }

	/**
	 * @param null $locale
	 * @return bool
	 */
    public function hasTranslation($locale = null)
    {
        $locale = $locale ?: $this->getLocale();

        foreach ($this->translations as $translation)
        {
            if ($translation->getAttribute($this->getLocaleKey()) == $locale)
            {
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
        $config = App::make('config');
        return get_class($this) . $config->get('translatable::translation_suffix', 'Translation');
    }

	/**
	 * @return mixed
	 */
	public function getRelationKey()
    {
        return $this->translationForeignKey ?: $this->getForeignKey();
    }

	/**
	 * @return mixed
	 */
	public function getLocaleKey()
    {
        $config = App::make('config');
        return $this->localeKey ?: $config->get('translatable::locale_key', 'locale');
    }

	/**
	 * @return mixed
	 */
	public function translations()
    {
        return $this->hasMany($this->getTranslationModelName(), $this->getRelationKey());
    }

	/**
	 * @param $key
	 * @return mixed
	 */
	public function getAttribute($key)
    {
        if ($this->isKeyReturningTranslationText($key))
        {
            return $this->getTranslation($this->getForcedLocale())->$key;
        }
       return parent::getAttribute($key);
    }

	/**
	 * @param $key
	 * @param $value
	 */
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

	/**
	 * @param array $options
	 * @return bool
	 */
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
                return $this->saveTranslations();
            }
        }
        elseif (parent::save($options))
        {
            // We save the translations only if the instance is saved in the database.
            return $this->saveTranslations();
        }
        return false;
    }

	/**
	 * @param array $attributes
	 * @return mixed
	 */
	public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();

        foreach ($attributes as $key => $values)
        {
            if ($this->isKeyALocale($key))
            {
                $translation = $this->getTranslation($key, false);

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

	protected function getForcedLocale()
	{
		return $this->forcedLocale;
	}
	/**
	 * @param $key
	 * @return null
	 */
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

	/**
	 * @param $key
	 * @return bool
	 */
	protected function isKeyReturningTranslationText($key)
    {
        return in_array($key, $this->translatedAttributes);
    }

	/**
	 * @param $key
	 * @return bool
	 */
	protected function isKeyALocale($key)
    {
        $locales = $this->getLocales();
        return in_array($key, $locales);
    }

	/**
	 * @return mixed
	 */
	protected function getLocales()
    {
        $config = App::make('config');
        return $config->get('translatable::locales', array());
    }

	/**
	 * @return bool
	 */
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

	/**
	 * @param Model $translation
	 * @return bool
	 */
	protected function isTranslationDirty(Model $translation)
    {
        $dirtyAttributes = $translation->getDirty();
        unset($dirtyAttributes[$this->getLocaleKey()]);
        return count($dirtyAttributes) > 0;
    }

	/**
	 * @param $locale
	 * @return mixed
	 */
	protected function getNewTranslationInstance($locale)
    {
        $modelName = $this->getTranslationModelName();
        $translation = new $modelName;
        $translation->setAttribute($this->getLocaleKey(), $locale);
        return $translation;
    }

	/**
	 * Get locale
	 *
	 * @return string
	 */
	protected function getLocale()
	{
		return App::getLocale();
	}
	/**
	 * @param $key
	 * @return bool
	 */
	public function __isset($key)
    {
        return (in_array($key, $this->translatedAttributes) || parent::__isset($key));
    }

}
