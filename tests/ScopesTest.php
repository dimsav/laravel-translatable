<?php

use Dimsav\Translatable\Test\Model\Country;
use Dimsav\Translatable\Test\Model\Vegetable;

class ScopesTest extends TestsBase
{
    public function test_translated_in_scope_returns_only_translated_records_for_this_locale()
    {
        $translatedCountries = Country::translatedIn('fr')->get();
        $this->assertEquals($translatedCountries->count(), 1);
    }

    public function test_translated_in_scope_works_with_default_locale()
    {
        App::setLocale('de');
        $translatedCountries = Country::translatedIn()->get();

        $this->assertSame($translatedCountries->count(), 1);
        $this->assertSame('Griechenland', $translatedCountries->first()->name);
    }

    public function test_not_translated_in_scope_returns_only_not_translated_records_for_this_locale()
    {
        $notTranslatedCountries = Country::notTranslatedIn('en')->get();
        $this->assertCount(2, $notTranslatedCountries);

        foreach ($notTranslatedCountries as $notTranslatedCountry) {
            $this->assertFalse($notTranslatedCountry->hasTranslation('en'));
        }
    }

    public function test_not_translated_in_scope_works_with_default_locale()
    {
        App::setLocale('en');
        $notTranslatedCountries = Country::notTranslatedIn()->get();
        $this->assertCount(2, $notTranslatedCountries);

        foreach ($notTranslatedCountries as $notTranslatedCountry) {
            $this->assertFalse($notTranslatedCountry->hasTranslation('en'));
        }
    }

    public function test_translated_scope_returns_records_with_at_least_one_translation()
    {
        $translatedCountries = Country::translated()->get();
        $this->assertEquals($translatedCountries->count(), 2);
    }

    public function test_lists_of_translated_fields()
    {
        App::setLocale('de');
        App::make('config')->set('translatable.to_array_always_loads_translations', false);

        $arr = Country::listsTranslations('name')->get()->toArray();

        $this->assertEquals(1, count($arr));
        $this->assertEquals('1', $arr[0]['id']);
        $this->assertEquals('Griechenland', $arr[0]['name']);
    }

    public function test_lists_of_translated_fields_with_fallback()
    {
        App::make('config')->set('translatable.fallback_locale', 'en');
        App::make('config')->set('translatable.to_array_always_loads_translations', false);
        App::setLocale('de');
        $country = new Country();
        $country->useTranslationFallback = true;
        $list = [[
            'id'   => 1,
            'name' => 'Griechenland',
        ], [
            'id'   => 2,
            'name' => 'France',
        ]];

        $arr = $country->listsTranslations('name')->get()->toArray();

        $this->assertEquals(2, count($arr));

        $this->assertEquals(1, $arr[0]['id']);
        $this->assertEquals('Griechenland', $arr[0]['name']);

        $this->assertEquals(2, $arr[1]['id']);
        $this->assertEquals('France', $arr[1]['name']);
    }

    public function test_lists_of_translated_fields_disable_autoload_translations()
    {
        App::setLocale('de');
        App::make('config')->set('translatable.to_array_always_loads_translations', true);

        $list = [[
            'id'   => 1,
            'name' => 'Griechenland',
        ]];
        Country::disableAutoloadTranslations();
        $this->assertEquals($list, Country::listsTranslations('name')->get()->toArray());
        Country::defaultAutoloadTranslations();
    }

    public function test_scope_withTranslation_without_fallback()
    {
        $result = Country::withTranslation()->first();
        $loadedTranslations = $result->toArray()['translations'];
        $this->assertCount(1, $loadedTranslations);
        $this->assertSame('Greece', $loadedTranslations[0]['name']);
    }

    public function test_scope_withTranslation_with_fallback()
    {
        App::make('config')->set('translatable.fallback_locale', 'de');
        App::make('config')->set('translatable.use_fallback', true);

        $result = Country::withTranslation()->first();
        $loadedTranslations = $result->toArray()['translations'];
        $this->assertCount(2, $loadedTranslations);
        $this->assertSame('Greece', $loadedTranslations[0]['name']);
        $this->assertSame('Griechenland', $loadedTranslations[1]['name']);
    }

    public function test_scope_withTranslation_with_country_based_fallback()
    {
        App::make('config')->set('translatable.fallback_locale', 'en');
        App::make('config')->set('translatable.use_fallback', true);
        App::setLocale('en-GB');
        $result = Vegetable::withTranslation()->find(1)->toArray();
        $this->assertSame('courgette', $result['name']);

        App::setLocale('de-CH');
        $result = Vegetable::withTranslation()->find(1)->toArray();
        $expectedTranslations = [
            ['name' => 'zucchini', 'locale' => 'en'],
            ['name' => 'Zucchini', 'locale' => 'de'],
            ['name' => 'Zucchetti', 'locale' => 'de-CH'],
        ];
        $translations = $result['translations'];

        $this->assertEquals(3, count($translations));

        $this->assertEquals('en', $translations[0]['locale']);
        $this->assertEquals('zucchini', $translations[0]['name']);

        $this->assertEquals('de', $translations[1]['locale']);
        $this->assertEquals('Zucchini', $translations[1]['name']);

        $this->assertEquals('de-CH', $translations[2]['locale']);
        $this->assertEquals('Zucchetti', $translations[2]['name']);
    }

    public function test_whereTranslation_filters_by_translation()
    {
        /** @var Country $country */
        $country = Country::whereTranslation('name', 'Greece')->first();
        $this->assertSame('gr', $country->code);
    }

    public function test_orWhereTranslation_filters_by_translation()
    {
        $result = Country::whereTranslation('name', 'Greece')->orWhereTranslation('name', 'France')->get();
        $this->assertCount(2, $result);
        $this->assertSame('Greece', $result[0]->name);
        $this->assertSame('France', $result[1]->name);
    }

    public function test_whereTranslation_filters_by_translation_and_locale()
    {
        Country::create(['code' => 'some-code', 'name' => 'Griechenland']);

        $this->assertSame(2, Country::whereTranslation('name', 'Griechenland')->count());

        $result = Country::whereTranslation('name', 'Griechenland', 'de')->get();
        $this->assertSame(1, $result->count());
        $this->assertSame('gr', $result->first()->code);
    }

    public function test_whereTranslationLike_filters_by_translation()
    {
        /** @var Country $country */
        $country = Country::whereTranslationLike('name', '%Greec%')->first();
        $this->assertSame('gr', $country->code);
    }

    public function test_orWhereTranslationLike_filters_by_translation()
    {
        $result = Country::whereTranslationLike('name', '%eece%')->orWhereTranslationLike('name', '%ance%')->get();
        $this->assertCount(2, $result);
        $this->assertSame('Greece', $result[0]->name);
        $this->assertSame('France', $result[1]->name);
    }

    public function test_whereTranslationLike_filters_by_translation_and_locale()
    {
        Country::create(['code' => 'some-code', 'name' => 'Griechenland']);

        $this->assertSame(2, Country::whereTranslationLike('name', 'Griechen%')->count());

        $result = Country::whereTranslationLike('name', '%riechenlan%', 'de')->get();
        $this->assertSame(1, $result->count());
        $this->assertSame('gr', $result->first()->code);
    }

    public function test_orderByTranslation_sorts_by_key_asc()
    {
        $result = Country::orderByTranslation('name')->get();
        $this->assertSame(2, $result->first()->id);
    }

    public function test_orderByTranslation_sorts_by_key_desc()
    {
        $result = Country::orderByTranslation('name', 'desc')->get();
        $this->assertSame(1, $result->first()->id);
    }
}
