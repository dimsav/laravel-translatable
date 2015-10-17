<?php

use Dimsav\Translatable\Test\Model\Country;
use Dimsav\Translatable\Test\Model\CountryStrict;
use Dimsav\Translatable\Test\Model\CountryWithCustomLocaleKey;

class ScopesTest extends TestsBase
{
    public function test_translated_in_scope_returns_only_translated_records_for_this_locale()
    {
        $translatedCountries = Country::translatedIn('fr')->get();
        $this->assertEquals($translatedCountries->count(), 1);
    }

    public function test_translated_scope_returns_records_with_at_least_one_translation()
    {
        $translatedCountries = Country::translated()->get();
        $this->assertEquals($translatedCountries->count(), 2);
    }

    public function test_lists_of_translated_fields()
    {
        App::setLocale('de');
        $list = [[
            'id' => '1',
            'name' => 'Griechenland',
        ]];
        $this->assertEquals($list, Country::listsTranslations('name')->get()->toArray());
    }

    public function test_lists_of_translated_fields_with_fallback()
    {
        App::make('config')->set('translatable.fallback_locale', 'en');
        App::setLocale('de');
        $country = new Country();
        $country->useTranslationFallback = true;
        $list = [[
            'id' => '1',
            'name' => 'Griechenland',
        ],[
            'id' => '2',
            'name' => 'France',
        ]];
        $this->assertEquals($list, $country->listsTranslations('name')->get()->toArray());
    }

}
