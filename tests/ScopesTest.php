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

        $list = [[
            'id'   => '1',
            'name' => 'Griechenland',
        ]];
        $this->assertArraySubset($list, Country::listsTranslations('name')->get()->toArray());
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
        $this->assertArraySubset($list, $country->listsTranslations('name')->get()->toArray());
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
        $this->assertArraySubset($expectedTranslations, $result['translations']);
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
}
