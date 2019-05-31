<?php

namespace Dimsav\Translatable;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Dimsav\Translatable\Exception\LocalesNotDefinedException;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;

class Locales implements Arrayable, ArrayAccess
{
    /**
     * @var ConfigContract
     */
    protected $config;

    /**
     * @var TranslatorContract
     */
    protected $translator;

    /**
     * @var array
     */
    protected $locales = [];

    public function __construct(ConfigContract $config, TranslatorContract $translator)
    {
        $this->config = $config;
        $this->translator = $translator;

        $this->load();
    }

    public function load(): void
    {
        $localesConfig = (array) $this->config->get('translatable.locales', []);

        if (empty($localesConfig)) {
            throw new LocalesNotDefinedException('Please make sure you have run "php artisan config:publish dimsav/laravel-translatable" and that the locales configuration is defined.');
        }

        $this->locales = [];
        foreach ($localesConfig as $key => $locale) {
            if (is_string($key) && is_array($locale)) {
                $this->locales[$key] = $key;
                foreach ($locale as $country) {
                    $countryLocale = $this->getCountryLocale($key, $country);
                    $this->locales[$countryLocale] = $countryLocale;
                }
            } else {
                $this->locales[$locale] = $locale;
            }
        }
    }

    public function all(): array
    {
        return array_values($this->locales);
    }

    public function current()
    {
        return $this->config->get('translatable.locale') ?: $this->translator->getLocale();
    }

    public function has(string $locale): bool
    {
        return isset($this->locales[$locale]);
    }

    public function get(string $locale): ?string
    {
        return $this->locales[$locale] ?? null;
    }

    public function add(string $locale): void
    {
        $this->locales[$locale] = $locale;
    }

    public function forget(string $locale): void
    {
        unset($this->locales[$locale]);
    }

    public function getLocaleSeparator(): string
    {
        return $this->config->get('translatable.locale_separator') ?: '-';
    }

    public function getCountryLocale(string $locale, string $country): string
    {
        return $locale.$this->getLocaleSeparator().$country;
    }

    public function isLocaleCountryBased(string $locale): bool
    {
        return strpos($locale, $this->getLocaleSeparator()) !== false;
    }

    public function getLanguageFromCountryBasedLocale(string $locale): string
    {
        return explode($this->getLocaleSeparator(), $locale)[0];
    }

    public function toArray(): array
    {
        return $this->all();
    }

    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    public function offsetGet($key): ?string
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value)
    {
        if (is_string($key) && is_string($value)) {
            $this->add($this->getCountryLocale($key, $value));
        } elseif (is_string($value)) {
            $this->add($value);
        }
    }

    public function offsetUnset($key)
    {
        $this->forget($key);
    }
}
