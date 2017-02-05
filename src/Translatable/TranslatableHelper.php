<?php
namespace Dimsav\Translatable;


class TranslatableHelper {

	use Translatable {
		Translatable::getLocales as _getLocales;
		Translatable::isLocaleCountryBased as _isLocaleCountryBased;
		Translatable::getLanguageFromCountryBasedLocale as _getLanguageFromCountryBasedLocale;
		Translatable::getLocaleSeparator as _getLocalaSeparator;
	}

	private $config;

	public function __construct($config) {
		$this->config = $config;
	}


	/**
	 * Get the active locales.
	 *
	 * @return array
	 * @throws Exception\LocalesNotDefinedException
	 */
	public function getLocales() {
		return $this->_getLocales();
	}

	/**
	 * Check if a locale is country based. (.ie. en-UK instead of just en)
	 *
	 * @param string	$locale
	 * @return bool
	 */
	public function isLocaleCountryBased($locale) {
		return $this->_isLocaleCountryBased($locale);
	}

	/**
	 * Get the language from a country based locale (i.e. en-UK => en)
	 *
	 * @param $locale	string
	 * @return string
	 */
	public function getLanguageFromCountryBasedLocale($locale) {
		return $this->_getLanguageFromCountryBasedLocale($locale);
	}

	/**
	 * Get the current locale separator (en-US => -, nl_BE => _)
	 *
	 * @return string
	 */
	public function getLocaleSeparator() {
		return $this->_getLocaleSeparator();
	}

	/**
	 * Returns the current config locale or the current application locale
	 *
	 * @return string
	 */
	public function current() {
		return $this->config->get('translatable.locale')
			?: app()->make('translator')->getLocale();
	}


}




