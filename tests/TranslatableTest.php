<?php

use Dimsav\Translatable\Test\Model\Country;

class TranslatableTests extends TestsBase {

    public function testTranslationModelName() {
        $country = new Country;
        $this->assertEquals(
            'Dimsav\Translatable\Test\Model\CountryTranslation',
            $country->getTranslationModelNameDefault());
    }

    public function testTranslationModelCustomName() {
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

    public function testRelationKey() {
        $country = new Country;

        $this->assertEquals('country_id', $country->getRelationKey());

        $country->translationForeignKey = 'my_awesome_key';
        $this->assertEquals('my_awesome_key', $country->getRelationKey());
    }

    public function testGettingTranslationModel() {
        /** @var Country $country */
        $country = Country::where('iso', '=', 'gr')->first();

        $englishTranslation = $country->getTranslationModel('el');
        $this->assertEquals('Ελλάδα', $englishTranslation->name);

        $englishTranslation = $country->getTranslationModel('en');
        $this->assertEquals('Greece', $englishTranslation->name);

        $this->app->setLocale('el');
        $englishTranslation = $country->getTranslationModel();
        $this->assertEquals('Ελλάδα', $englishTranslation->name);

        $this->app->setLocale('en');
        $englishTranslation = $country->getTranslationModel();
        $this->assertEquals('Greece', $englishTranslation->name);
    }

    public function testGettingAttributeFromTranslation() {
        $country = Country::where('iso', '=', 'gr')->first();
        $this->app->setLocale('en');
        $this->assertEquals('Greece', $country->name);

        $this->app->setLocale('el');
        $this->assertEquals('Ελλάδα', $country->name);
    }

    public function testSavingTranslation() {
        $this->app->setLocale('en');
        $country = Country::where('iso', '=', 'gr')->first();

        $country->setAttribute('name', 'abcd');
        $this->assertEquals('abcd', $country->name);

        $country->name = '1234';
        $this->assertEquals('1234', $country->name);
        $country->save();

        $country = Country::where('iso', '=', 'gr')->first();
        $this->assertEquals('1234', $country->name);
    }

    public function testCreatingInstanceWithoutTranslation() {
        $this->app->setLocale('en');
        $country = new Country;
        $country->iso = 'be';
        $country->save();

        $country = Country::where('iso', '=', 'be')->first();
        $country->name = 'Belgium';
        $country->save();

        $country = Country::where('iso', '=', 'be')->first();
        $this->assertEquals('Belgium', $country->name);

    }

    public function testCreatingInstanceWithTranslation() {
        $this->app->setLocale('en');
        $country = new Country;
        $country->iso = 'be';
        $country->name = 'Belgium';
        $country->save();

        $country = Country::where('iso', '=', 'be')->first();
        $this->assertEquals('Belgium', $country->name);
    }

    public function testCreatingInstanceUsingMassAssignment() {
        $this->app->setLocale('en');
        $data = array(
            'iso' => 'be',
            'name' => 'Belgium',
        );
        $country = Country::create($data);
        $this->assertEquals('be', $country->iso);
        $this->assertEquals('Belgium', $country->name);
    }

}