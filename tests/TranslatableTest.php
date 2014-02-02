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
        $this->app->setLocale('en');
        $country = Country::where('iso', '=', 'gr')->first();

        $this->assertEquals('Greece', $country->name);
    }
}