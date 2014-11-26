<?php

use Dimsav\Translatable\Test\Model\Country;
use Dimsav\Translatable\Test\Model\CountryStrict;
use Dimsav\Translatable\Test\Model\CountryWithCustomLocaleKey;

class TranslatableTest extends TestsBase {

    /**
     * @test
     */
    public function it_finds_the_default_translation_class()
    {
        $country = new Country;
        $this->assertEquals(
            'Dimsav\Translatable\Test\Model\CountryTranslation',
            $country->getTranslationModelNameDefault());
    }

    /**
     * @test
     */
    public function it_finds_the_translation_class_with_suffix_set()
    {
        App::make('config')->set('translatable::translation_suffix', 'Trans');
        $country = new Country;
        $this->assertEquals(
            'Dimsav\Translatable\Test\Model\CountryTrans',
            $country->getTranslationModelName());
    }

    /**
     * @test
     */
    public function it_returns_custom_TranslationModelName()
    {
        $country = new Country;

        $this->assertEquals(
            $country->getTranslationModelNameDefault(),
            $country->getTranslationModelName()
        );

        $country->translationModel = 'MyAwesomeCountryTranslation';
        $this->assertEquals(
            'MyAwesomeCountryTranslation',
            $country->getTranslationModelName()
        );
    }

    /**
     * @test
     */
    public function it_returns_relation_key()
    {
        $country = new Country;
        $this->assertEquals('country_id', $country->getRelationKey());

        $country->translationForeignKey = 'my_awesome_key';
        $this->assertEquals('my_awesome_key', $country->getRelationKey());
    }

    /**
     * @test
     */
    public function it_returns_the_translation()
    {
        /** @var Country $country */
        $country = Country::whereIso('gr')->first();

        $englishTranslation = $country->translate('el');
        $this->assertEquals('Ελλάδα', $englishTranslation->name);

        $englishTranslation = $country->translate('en');
        $this->assertEquals('Greece', $englishTranslation->name);

        $this->app->setLocale('el');
        $englishTranslation = $country->translate();
        $this->assertEquals('Ελλάδα', $englishTranslation->name);

        $this->app->setLocale('en');
        $englishTranslation = $country->translate();
        $this->assertEquals('Greece', $englishTranslation->name);
    }

    /**
     * @test
     */
    public function it_saves_translations()
    {
        $country = Country::whereIso('gr')->first();

        $country->name = '1234';
        $country->save();

        $country = Country::whereIso('gr')->first();
        $this->assertEquals('1234', $country->name);
    }

    /**
     * @test
     */
    public function it_uses_default_locale_to_return_translations()
    {
        $country = Country::whereIso('gr')->first();

        $country->translate('el')->name = 'abcd';

        $this->app->setLocale('el');
        $this->assertEquals('abcd', $country->name);
        $country->save();

        $country = Country::whereIso('gr')->first();
        $this->assertEquals('abcd', $country->translate('el')->name);
    }

    /**
     * @test
     */
    public function it_creates_translations()
    {
        $country = new Country;
        $country->iso = 'be';
        $country->save();

        $country = Country::whereIso('be')->first();
        $country->name = 'Belgium';
        $country->save();

        $country = Country::whereIso('be')->first();
        $this->assertEquals('Belgium', $country->name);

    }

    /**
     * @test
     */
    public function it_creates_translations_using_the_shortcut()
    {
        $country = new Country;
        $country->iso = 'be';
        $country->name = 'Belgium';
        $country->save();

        $country = Country::whereIso('be')->first();
        $this->assertEquals('Belgium', $country->name);
    }

    /**
     * @test
     */
    public function it_creates_translations_using_mass_assignment()
    {
        $data = array(
            'iso' => 'be',
            'name' => 'Belgium',
        );
        $country = Country::create($data);
        $this->assertEquals('be', $country->iso);
        $this->assertEquals('Belgium', $country->name);
    }

    /**
     * @test
     */
    public function it_creates_translations_using_mass_assignment_and_locales()
    {
        $data = array(
            'iso' => 'be',
            'en' => ['name' => 'Belgium'],
            'fr' => ['name' => 'Belgique']
        );
        $country = Country::create($data);
        $this->assertEquals('be', $country->iso);
        $this->assertEquals('Belgium', $country->translate('en')->name);
        $this->assertEquals('Belgique', $country->translate('fr')->name);

        $country = Country::whereIso('be')->first();
        $this->assertEquals('Belgium', $country->translate('en')->name);
        $this->assertEquals('Belgique', $country->translate('fr')->name);
    }

    /**
     * @test
     */
    public function it_skips_mass_assignment_if_attributes_non_fillable()
    {
        $data = array(
            'iso' => 'be',
            'en' => ['name' => 'Belgium'],
            'fr' => ['name' => 'Belgique']
        );
        $country = CountryStrict::create($data);
        $this->assertEquals('be', $country->iso);
        $this->assertNull($country->translate('en'));
        $this->assertNull($country->translate('fr'));
    }

    /**
     * @test
     */
    public function it_returns_if_object_has_translation()
    {
        $country = Country::find(1);
        $this->assertTrue($country->hasTranslation('en'));
        $this->assertFalse($country->hasTranslation('abc'));
    }

    /**
     * @test
     */
    public function it_returns_default_translation()
    {
        App::make('config')->set('translatable::fallback_locale', 'de');

        $country = Country::find(1);
        $this->assertSame($country->getTranslation('ch', true)->name, 'Griechenland');
        $this->assertSame($country->translateOrDefault('ch')->name, 'Griechenland');
        $this->assertSame($country->getTranslation('ch', false), null);
    }

    /**
     * @test
     */
    public function fallback_option_in_config_overrides_models_fallback_option()
    {
        App::make('config')->set('translatable::fallback_locale', 'de');

        $country = Country::find(1);
        $this->assertEquals($country->getTranslation('ch', true)->locale, 'de');

        $country->useTranslationFallback = false;
        $this->assertEquals($country->getTranslation('ch', true)->locale, 'de');

        $country->useTranslationFallback = true;
        $this->assertEquals($country->getTranslation('ch')->locale, 'de');

        $country->useTranslationFallback = false;
        $this->assertSame($country->getTranslation('ch'), null);
    }

    /**
     * @test
     */
    public function it_returns_null_if_fallback_is_not_defined()
    {
        App::make('config')->set('translatable::fallback_locale', 'ch');

        $country = Country::find(1);
        $this->assertSame($country->getTranslation('pl', true), null);
    }

    /**
     * @test
     */
    public function it_fills_a_non_default_language_with_fallback_set()
    {
        App::make('config')->set('translatable::fallback_locale', 'en');

        $country = new Country;
        $country->fill([
            'iso' => 'gr',
            'en' => ['name' => 'Greece'],
            'de' => ['name' => 'Griechenland'],
        ]);

        $this->assertEquals($country->translate('en')->name, 'Greece');
    }

    /**
     * @test
     */
    public function it_creates_a_new_translation()
    {
        App::make('config')->set('translatable::fallback_locale', 'en');

        $country = Country::create(['iso' => 'gr']);
        $country->getNewTranslation('en')->name = 'Greece';
        $country->save();

        $this->assertEquals($country->translate('en')->name, 'Greece');
    }

    /**
     * @test
     */
    public function the_locale_key_is_locale_by_default()
    {
        $country = Country::find(1);
        $this->assertEquals($country->getLocaleKey(), 'locale');
    }

    /**
     * @test
     */
    public function the_locale_key_can_be_overridden_in_configuration()
    {
        App::make('config')->set('translatable::locale_key', 'language_id');

        $country = Country::find(1);
        $this->assertEquals($country->getLocaleKey(), 'language_id');
    }

    /**
     * @test
     */
    public function the_locale_key_can_be_customized_per_model()
    {
        $country = CountryWithCustomLocaleKey::find(1);
        $this->assertEquals($country->getLocaleKey(), 'language_id');
    }

    /**
     * @test
     */
    public function it_reads_the_configuration()
    {
        $this->assertEquals(App::make('config')->get('translatable::translation_suffix'), 'Translation');
    }

    /**
     * @test
     */
    public function translated_in_scope_returns_only_translated_records_for_this_locale()
    {
        $translatedCountries = Country::translatedIn('fr')->get();
        $this->assertEquals($translatedCountries->count(), 1);
    }

    /**
     * @test
     */
    public function translated_scope_returns_records_with_at_least_one_translation()
    {
        $translatedCountries = Country::translated()->get();
        $this->assertEquals($translatedCountries->count(), 2);
    }

    /**
     * @test
     */
    public function getting_translation_does_not_create_translation()
    {
        $country = Country::with('translations')->find(1);
        $translation = $country->getTranslation('abc', false);
        $this->assertSame($translation, null);
    }

    /**
     * @test
     */
    public function getting_translated_field_does_not_create_translation()
    {
        $this->app->setLocale('en');
        $country = new Country(['iso' => 'pl']);
        $country->save();

        $country->name;

        $this->assertSame($country->getTranslation('en'), null);
    }

    /**
     * @test
     * @expectedException Dimsav\Translatable\Exception\LocalesNotDefinedException
     */
    public function if_locales_are_not_defined_throw_exception()
    {
        $this->app->config->set('translatable::locales', []);
        new Country(['iso' => 'pl']);
    }

    /**
     * @test
     */
    public function it_has_methods_that_return_always_a_translation()
    {
        $country = Country::find(1)->first();
        $this->assertSame('abc', $country->translateOrNew('abc')->locale);
    }

    /**
     * @test
     */
    public function configuration_overrides_fillable()
    {
        App::make('config')->set('translatable::always_fillable', true);

        $country = new CountryStrict([
            'en' => ['name' => 'Not fillable'],
            'iso' => 'te'
        ]);

        $this->assertSame($country->getTranslation('en')->name, 'Not fillable');
    }
}
