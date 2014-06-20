<?php

use Dimsav\Translatable\Test\Model\Country;
use Dimsav\Translatable\Test\Model\CountryStrict;

class TranslatableTests extends TestsBase {

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
        $this->assertNull($country->translate('en')->name);
        $this->assertNull($country->translate('fr')->name);
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
        $this->assertEquals(App::make('config')->get('app.fallback_locale'), 'de');

        $country = Country::find(1);
        $this->assertEquals($country->getTranslation('ch', true)->name, 'Griechenland');
        $this->assertEquals($country->translateOrDefault('ch')->name, 'Griechenland');
        $this->assertEquals($country->getTranslation('ch', false)->name, null);
    }

}