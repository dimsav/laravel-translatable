<?php

use Dimsav\Translatable\Test\Model\Food;
use Dimsav\Translatable\Test\Model\Person;
use Dimsav\Translatable\Test\Model\Country;
use Dimsav\Translatable\Test\Model\CountryStrict;
use Dimsav\Translatable\Test\Model\CountryWithCustomLocaleKey;

class TranslatableTest extends TestsBase
{
    public function test_it_finds_the_default_translation_class()
    {
        $country = new Country();
        $this->assertEquals(
            'Dimsav\Translatable\Test\Model\CountryTranslation',
            $country->getTranslationModelNameDefault());
    }

    public function test_it_finds_the_translation_class_with_suffix_set()
    {
        App::make('config')->set('translatable.translation_suffix', 'Trans');
        $country = new Country();
        $this->assertEquals(
            'Dimsav\Translatable\Test\Model\CountryTrans',
            $country->getTranslationModelName());
    }

    public function test_it_returns_custom_TranslationModelName()
    {
        $country = new Country();

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

    public function test_it_returns_relation_key()
    {
        $country = new Country();
        $this->assertEquals('country_id', $country->getRelationKey());

        $country->translationForeignKey = 'my_awesome_key';
        $this->assertEquals('my_awesome_key', $country->getRelationKey());
    }

    public function test_it_returns_the_translation()
    {
        /** @var Country $country */
        $country = Country::whereCode('gr')->first();

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

    public function test_it_returns_the_translation_with_accessor()
    {
        /** @var Country $country */
        $country = Country::whereCode('gr')->first();

        $this->assertEquals('Ελλάδα', $country->{'name:el'});
        $this->assertEquals('Greece', $country->{'name:en'});
    }

    public function test_it_returns_null_when_the_locale_doesnt_exist()
    {
        /** @var Country $country */
        $country = Country::whereCode('gr')->first();

        $this->assertSame(null, $country->{'name:unknown-locale'});
    }

    public function test_it_saves_translations()
    {
        $country = Country::whereCode('gr')->first();

        $country->name = '1234';
        $country->save();

        $country = Country::whereCode('gr')->first();
        $this->assertEquals('1234', $country->name);
    }

    public function test_it_saves_translations_with_mutator()
    {
        $country = Country::whereCode('gr')->first();

        $country->{'name:en'} = '1234';
        $country->{'name:el'} = '5678';
        $country->save();

        $country = Country::whereCode('gr')->first();

        $this->app->setLocale('en');
        $translation = $country->translate();
        $this->assertEquals('1234', $translation->name);

        $this->app->setLocale('el');
        $translation = $country->translate();
        $this->assertEquals('5678', $translation->name);
    }

    public function test_it_uses_default_locale_to_return_translations()
    {
        $country = Country::whereCode('gr')->first();

        $country->translate('el')->name = 'abcd';

        $this->app->setLocale('el');
        $this->assertEquals('abcd', $country->name);
        $country->save();

        $country = Country::whereCode('gr')->first();
        $this->assertEquals('abcd', $country->translate('el')->name);
    }

    public function test_it_creates_translations()
    {
        $country = new Country();
        $country->code = 'be';
        $country->save();

        $country = Country::whereCode('be')->first();
        $country->name = 'Belgium';
        $country->save();

        $country = Country::whereCode('be')->first();
        $this->assertEquals('Belgium', $country->name);
    }

    public function test_it_creates_translations_using_the_shortcut()
    {
        $country = new Country();
        $country->code = 'be';
        $country->name = 'Belgium';
        $country->save();

        $country = Country::whereCode('be')->first();
        $this->assertEquals('Belgium', $country->name);
    }

    public function test_it_creates_translations_using_mass_assignment()
    {
        $data = [
            'code' => 'be',
            'name' => 'Belgium',
        ];
        $country = Country::create($data);
        $this->assertEquals('be', $country->code);
        $this->assertEquals('Belgium', $country->name);
    }

    public function test_it_creates_translations_using_mass_assignment_and_locales()
    {
        $data = [
            'code' => 'be',
            'en'   => ['name' => 'Belgium'],
            'fr'   => ['name' => 'Belgique'],
        ];
        $country = Country::create($data);
        $this->assertEquals('be', $country->code);
        $this->assertEquals('Belgium', $country->translate('en')->name);
        $this->assertEquals('Belgique', $country->translate('fr')->name);

        $country = Country::whereCode('be')->first();
        $this->assertEquals('Belgium', $country->translate('en')->name);
        $this->assertEquals('Belgique', $country->translate('fr')->name);
    }

    /**
     * @expectedException Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function test_it_skips_mass_assignment_if_attributes_non_fillable()
    {
        $data = [
            'code' => 'be',
            'en'   => ['name' => 'Belgium'],
            'fr'   => ['name' => 'Belgique'],
        ];
        $country = CountryStrict::create($data);
        $this->assertEquals('be', $country->code);
        $this->assertNull($country->translate('en'));
        $this->assertNull($country->translate('fr'));
    }

    public function test_it_returns_if_object_has_translation()
    {
        $country = Country::find(1);
        $this->assertTrue($country->hasTranslation('en'));
        $this->assertFalse($country->hasTranslation('abc'));
    }

    public function test_it_returns_default_translation()
    {
        App::make('config')->set('translatable.fallback_locale', 'de');

        $country = Country::find(1);
        $this->assertSame($country->getTranslation('ch', true)->name, 'Griechenland');
        $this->assertSame($country->translateOrDefault('ch')->name, 'Griechenland');
        $this->assertSame($country->getTranslation('ch', false), null);
    }

    public function test_fallback_option_in_config_overrides_models_fallback_option()
    {
        App::make('config')->set('translatable.fallback_locale', 'de');

        $country = Country::find(1);
        $this->assertEquals($country->getTranslation('ch', true)->locale, 'de');

        $country->useTranslationFallback = false;
        $this->assertEquals($country->getTranslation('ch', true)->locale, 'de');

        $country->useTranslationFallback = true;
        $this->assertEquals($country->getTranslation('ch')->locale, 'de');

        $country->useTranslationFallback = false;
        $this->assertSame($country->getTranslation('ch'), null);
    }

    public function test_configuration_defines_if_fallback_is_used()
    {
        App::make('config')->set('translatable.fallback_locale', 'de');
        App::make('config')->set('translatable.use_fallback', true);

        $country = Country::find(1);
        $this->assertEquals($country->getTranslation('ch')->locale, 'de');
    }

    public function test_useTranslationFallback_overrides_configuration()
    {
        App::make('config')->set('translatable.fallback_locale', 'de');
        App::make('config')->set('translatable.use_fallback', true);
        $country = Country::find(1);
        $country->useTranslationFallback = false;
        $this->assertSame($country->getTranslation('ch'), null);
    }

    public function test_it_returns_null_if_fallback_is_not_defined()
    {
        App::make('config')->set('translatable.fallback_locale', 'ch');

        $country = Country::find(1);
        $this->assertSame($country->getTranslation('pl', true), null);
    }

    public function test_it_fills_a_non_default_language_with_fallback_set()
    {
        App::make('config')->set('translatable.fallback_locale', 'en');

        $country = new Country();
        $country->fill([
            'code' => 'gr',
            'en'   => ['name' => 'Greece'],
            'de'   => ['name' => 'Griechenland'],
        ]);

        $this->assertEquals($country->translate('en')->name, 'Greece');
    }

    public function test_it_creates_a_new_translation()
    {
        App::make('config')->set('translatable.fallback_locale', 'en');

        $country = Country::create(['code' => 'gr']);
        $country->getNewTranslation('en')->name = 'Greece';
        $country->save();

        $this->assertEquals($country->translate('en')->name, 'Greece');
    }

    public function test_the_locale_key_is_locale_by_default()
    {
        $country = Country::find(1);
        $this->assertEquals($country->getLocaleKey(), 'locale');
    }

    public function test_the_locale_key_can_be_overridden_in_configuration()
    {
        App::make('config')->set('translatable.locale_key', 'language_id');

        $country = Country::find(1);
        $this->assertEquals($country->getLocaleKey(), 'language_id');
    }

    public function test_the_locale_key_can_be_customized_per_model()
    {
        $country = CountryWithCustomLocaleKey::find(1);
        $this->assertEquals($country->getLocaleKey(), 'language_id');
    }

    public function test_it_reads_the_configuration()
    {
        $this->assertEquals(App::make('config')->get('translatable.translation_suffix'), 'Translation');
    }

    public function test_getting_translation_does_not_create_translation()
    {
        $country = Country::with('translations')->find(1);
        $translation = $country->getTranslation('abc', false);
        $this->assertSame($translation, null);
    }

    public function test_getting_translated_field_does_not_create_translation()
    {
        $this->app->setLocale('en');
        $country = new Country(['code' => 'pl']);
        $country->save();

        $country->name;

        $this->assertSame($country->getTranslation('en'), null);
    }

    /**
     * @expectedException Dimsav\Translatable\Exception\LocalesNotDefinedException
     */
    public function test_if_locales_are_not_defined_throw_exception()
    {
        $this->app->config->set('translatable.locales', []);
        new Country(['code' => 'pl']);
    }

    public function test_it_has_methods_that_return_always_a_translation()
    {
        $country = Country::find(1)->first();
        $this->assertSame('abc', $country->translateOrNew('abc')->locale);
    }

    public function test_it_returns_if_attribute_is_translated()
    {
        $country = new Country();

        $this->assertTrue($country->isTranslationAttribute('name'));
        $this->assertFalse($country->isTranslationAttribute('some-field'));
    }

    public function test_config_overrides_apps_locale()
    {
        $country = Country::find(1);
        App::make('config')->set('translatable.locale', 'de');

        $this->assertSame('Griechenland', $country->name);
    }

    public function test_locales_as_array_keys_are_properly_detected()
    {
        $this->app->config->set('translatable.locales', ['en' => ['US', 'GB']]);

        $data = [
            'en'    => ['name' => 'French fries'],
            'en-US' => ['name' => 'American french fries'],
            'en-GB' => ['name' => 'Chips'],
        ];
        $frenchFries = Food::create($data);

        $this->assertSame('French fries', $frenchFries->getTranslation('en')->name);
        $this->assertSame('Chips', $frenchFries->getTranslation('en-GB')->name);
        $this->assertSame('American french fries', $frenchFries->getTranslation('en-US')->name);
    }

    public function test_locale_separator_can_be_configured()
    {
        $this->app->config->set('translatable.locales', ['en' => ['GB']]);
        $this->app->config->set('translatable.locale_separator', '_');
        $data = [
            'en_GB' => ['name' => 'Chips'],
        ];
        $frenchFries = Food::create($data);

        $this->assertSame('Chips', $frenchFries->getTranslation('en_GB')->name);
    }

    public function test_fallback_for_country_based_locales()
    {
        $this->app->config->set('translatable.use_fallback', true);
        $this->app->config->set('translatable.fallback_locale', 'fr');
        $this->app->config->set('translatable.locales', ['en' => ['US', 'GB'], 'fr']);
        $this->app->config->set('translatable.locale_separator', '-');
        $data = [
            'id'    => 1,
            'fr'    => ['name' => 'frites'],
            'en-GB' => ['name' => 'chips'],
            'en'    => ['name' => 'french fries'],
        ];
        Food::create($data);
        $fries = Food::find(1);
        $this->assertSame('french fries', $fries->getTranslation('en-US')->name);
    }

    public function test_fallback_for_country_based_locales_with_no_base_locale()
    {
        $this->app->config->set('translatable.use_fallback', true);
        $this->app->config->set('translatable.fallback_locale', 'en');
        $this->app->config->set('translatable.locales', ['pt' => ['PT', 'BR'], 'en']);
        $this->app->config->set('translatable.locale_separator', '-');
        $data = [
            'id'    => 1,
            'en'    => ['name' => 'chips'],
            'pt-PT' => ['name' => 'batatas fritas'],
        ];
        Food::create($data);
        $fries = Food::find(1);
        $this->assertSame('chips', $fries->getTranslation('pt-BR')->name);
    }

    public function test_to_array_and_fallback_with_country_based_locales_enabled()
    {
        $this->app->config->set('translatable.use_fallback', true);
        $this->app->config->set('translatable.fallback_locale', 'fr');
        $this->app->config->set('translatable.locales', ['en' => ['GB'], 'fr']);
        $this->app->config->set('translatable.locale_separator', '-');
        $data = [
            'id' => 1,
            'fr' => ['name' => 'frites'],
        ];
        Food::create($data);
        $fritesArray = Food::find(1)->toArray();
        $this->assertSame('frites', $fritesArray['name']);
    }

    public function test_it_skips_translations_in_to_array_when_config_is_set()
    {
        $this->app->config->set('translatable.to_array_always_loads_translations', false);
        $greece = Country::whereCode('gr')->first()->toArray();
        $this->assertFalse(isset($greece['name']));
    }

    public function test_it_returns_translations_in_to_array_when_config_is_set_but_translations_are_loaded()
    {
        $this->app->config->set('translatable.to_array_always_loads_translations', false);
        $greece = Country::whereCode('gr')->with('translations')->first()->toArray();
        $this->assertTrue(isset($greece['name']));
    }

    public function test_it_should_mutate_the_translated_attribute_if_a_mutator_is_set_on_model()
    {
        $person = new Person(['name' => 'john doe']);
        $person->save();
        $person = Person::find(1);
        $this->assertEquals('John doe', $person->name);
    }

    public function test_it_deletes_all_translations()
    {
        $country = Country::whereCode('gr')->first();
        $this->assertSame(4, count($country->translations));

        $country->deleteTranslations();

        $this->assertSame(0, count($country->translations));
        $country = Country::whereCode('gr')->first();
        $this->assertSame(0, count($country->translations));
    }

    public function test_it_deletes_translations_for_given_locales()
    {
        $country = Country::whereCode('gr')->with('translations')->first();
        $count = count($country->translations);

        $country->deleteTranslations('fr');

        $this->assertSame($count - 1, count($country->translations));
        $country = Country::whereCode('gr')->with('translations')->first();
        $this->assertSame($count - 1, count($country->translations));
        $this->assertSame(null, $country->translate('fr'));
    }

    public function test_passing_an_empty_array_should_not_delete_translations()
    {
        $country = Country::whereCode('gr')->with('translations')->first();
        $count = count($country->translations);

        $country->deleteTranslations([]);

        $country = Country::whereCode('gr')->with('translations')->first();
        $this->assertSame($count, count($country->translations));
    }

    public function test_fill_with_translation_key()
    {
        $country = new Country();
        $country->fill([
            'code'    => 'tr',
            'name:en' => 'Turkey',
            'name:de' => 'Türkei',
        ]);
        $this->assertEquals($country->translate('en')->name, 'Turkey');
        $this->assertEquals($country->translate('de')->name, 'Türkei');

        $country->save();
        $country = Country::whereCode('tr')->first();
        $this->assertEquals($country->translate('en')->name, 'Turkey');
        $this->assertEquals($country->translate('de')->name, 'Türkei');
    }

    public function test_it_uses_the_default_locale_from_the_model()
    {
        $country = new Country();
        $country->fill([
            'code'    => 'tn',
            'name:en' => 'Tunisia',
            'name:fr' => 'Tunisie',
        ]);
        $this->assertEquals($country->name, 'Tunisia');
        $country->setDefaultLocale('fr');
        $this->assertEquals($country->name, 'Tunisie');

        $country->setDefaultLocale(null);
        $country->save();
        $country = Country::whereCode('tn')->first();
        $this->assertEquals($country->name, 'Tunisia');
        $country->setDefaultLocale('fr');
        $this->assertEquals($country->name, 'Tunisie');
    }

    public function test_replicate_entity()
    {
        $apple = new Food();
        $apple->fill([
            'name:fr' => 'Pomme',
            'name:en' => 'Apple',
            'name:de' => 'Apfel',
        ]);
        $apple->save();

        $replicatedApple = $apple->replicateWithTranslations();
        $this->assertNotSame($replicatedApple->id, $apple->id);
        $this->assertEquals($replicatedApple->translate('fr')->name, $apple->translate('fr')->name);
        $this->assertEquals($replicatedApple->translate('en')->name, $apple->translate('en')->name);
        $this->assertEquals($replicatedApple->translate('de')->name, $apple->translate('de')->name);
    }

    public function test_getTranslationsArray()
    {
        Country::create([
            'code'    => 'tn',
            'name:en' => 'Tunisia',
            'name:fr' => 'Tunisie',
            'name:de' => 'Tunesien',
        ]);

        /** @var Country $country */
        $country = Country::where('code', 'tn')->first();

        $this->assertSame([
            'de' => ['name' => 'Tunesien'],
            'en' => ['name' => 'Tunisia'],
            'fr' => ['name' => 'Tunisie'],
        ], $country->getTranslationsArray());
    }

    public function test_fill_when_locale_key_unknown()
    {
        config(['translatable.locales' => ['en']]);

        $country = new Country();
        $country->fill([
            'code' => 'ua',
            'en'   => ['name' => 'Ukraine'],
            'ua'   => ['name' => 'Україна'], // "ua" is unknown, so must be ignored
        ]);

        $modelTranslations = [];

        foreach ($country->translations as $translation) {
            foreach ($country->translatedAttributes as $attr) {
                $modelTranslations[$translation->locale][$attr] = $translation->{$attr};
            }
        }

        $expectedTranslations = [
            'en' => ['name' => 'Ukraine'],
        ];

        $this->assertEquals($modelTranslations, $expectedTranslations);
    }

    public function test_fill_with_translation_key_when_locale_key_unknown()
    {
        config(['translatable.locales' => ['en']]);

        $country = new Country();
        $country->fill([
            'code'    => 'ua',
            'name:en' => 'Ukraine',
            'name:ua' => 'Україна', // "ua" is unknown, so must be ignored
        ]);

        $modelTranslations = [];

        foreach ($country->translations as $translation) {
            foreach ($country->translatedAttributes as $attr) {
                $modelTranslations[$translation->locale][$attr] = $translation->{$attr};
            }
        }

        $expectedTranslations = [
            'en' => ['name' => 'Ukraine'],
        ];

        $this->assertEquals($modelTranslations, $expectedTranslations);
    }

    public function test_it_uses_fallback_locale_if_default_is_empty()
    {
        App::make('config')->set('translatable.use_fallback', true);
        App::make('config')->set('translatable.use_property_fallback', true);
        App::make('config')->set('translatable.fallback_locale', 'en');
        $country = new Country();
        $country->fill([
            'code'    => 'tn',
            'name:en' => 'Tunisia',
            'name:fr' => '',
        ]);
        $this->app->setLocale('en');
        $this->assertEquals('Tunisia', $country->name);
        $this->app->setLocale('fr');
        $this->assertEquals('Tunisia', $country->name);
    }

    public function test_it_always_uses_value_when_fallback_not_available()
    {
        App::make('config')->set('translatable.fallback_locale', 'it');
        App::make('config')->set('translatable.use_fallback', true);

        $country = new Country();
        $country->fill([
            'code' => 'gr',
            'en' => ['name' => ''],
            'de' => ['name' => 'Griechenland'],
        ]);

        // verify translated attributed is correctly returned when empty (non-existing fallback is ignored)
        $this->app->setLocale('en');
        $this->assertEquals('', $country->getAttribute('name'));

        $this->app->setLocale('de');
        $this->assertEquals('Griechenland', $country->getAttribute('name'));
    }

    public function test_translation_with_multiconnection()
    {
        // Add country & translation in second db
        $country = new Country();
        $country->setConnection('mysql2');
        $country->code = 'sg';
        $country->{'name:sg'} = 'Singapore';
        $this->assertTrue($country->save());

        $countryId = $country->id;

        // Verify added country & translation in second db
        $country = new Country();
        $country->setConnection('mysql2');
        $sgCountry = $country->find($countryId);
        $this->assertEquals('Singapore', $sgCountry->translate('sg')->name);

        // Verify added country not in default db
        $country = new Country();
        $sgCountry = $country::where('code', 'sg')->get();
        $this->assertEmpty($sgCountry);

        // Verify added translation not in default db
        $country = new Country();
        $sgCountry = $country->find($countryId);
        $this->assertEmpty($sgCountry->translate('sg'));
    }

    public function test_empty_translated_attribute()
    {
        $country = Country::whereCode('gr')->first();
        $this->app->setLocale('invalid');
        $this->assertSame(null, $country->name);
    }
}
