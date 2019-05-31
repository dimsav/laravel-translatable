<?php

namespace Dimsav\Translatable;

use Dimsav\Translatable\Exception\LocalesNotDefinedException;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;

class Locales
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
    }

    public function getLocales(): array
    {
        if (empty($this->locales)) {
            $localesConfig = (array)$this->config->get('translatable.locales', []);

            if (empty($localesConfig)) {
                throw new LocalesNotDefinedException('Please make sure you have run "php artisan config:publish dimsav/laravel-translatable" and that the locales configuration is defined.');
            }

            foreach ($localesConfig as $key => $locale) {
                if (is_array($locale)) {
                    $this->locales[] = $key;
                    foreach ($locale as $countryLocale) {
                        $this->locales[] = $key . $this->getLocaleSeparator() . $countryLocale;
                    }
                } else {
                    $this->locales[] = $locale;
                }
            }
        }


        return $this->locales;
    }

    public function current()
    {
        return $this->config->get('translatable.locale') ?: $this->translator->getLocale();
    }

    public function isLocaleCountryBased(string $locale): bool
    {
        return strpos($locale, $this->getLocaleSeparator()) !== false;
    }

    public function getLanguageFromCountryBasedLocale(string $locale): string
    {
        return explode($this->getLocaleSeparator(), $locale)[0];
    }

    protected function getLocaleSeparator(): string
    {
        return $this->config->get('translatable.locale_separator', '-');
    }
}
