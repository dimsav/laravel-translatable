<?php

use Dimsav\Translatable\Test\Model\Country;
use Dimsav\Translatable\Test\Model\CountryStrict;

class TranslatableTests extends TestsBase {

    public function testTranslationModelName()
    {
        $country = new Country;
        $this->assertEquals(
            'Dimsav\Translatable\Test\Model\CountryTranslation',
            $country->getTranslationModelNameDefault());
    }

    public function testTranslationModelCustomName()
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

    public function testRelationKey()
    {
        $country = new Country;

        $this->assertEquals('country_id', $country->getRelationKey());

        $country->translationForeignKey = 'my_awesome_key';
        $this->assertEquals('my_awesome_key', $country->getRelationKey());
    }

    public function testGettingTranslationModel()
    {
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

    public function testGettingAttributeFromTranslation()
    {
        $country = Country::where('iso', '=', 'gr')->first();
        $this->assertEquals('Greece', $country->name);

        $this->app->setLocale('el');
        $this->assertEquals('Ελλάδα', $country->name);
    }

    public function testSavingTranslation()
    {
        $country = Country::where('iso', '=', 'gr')->first();

        $country->setAttribute('name', 'abcd');
        $this->assertEquals('abcd', $country->name);

        $country->name = '1234';
        $this->assertEquals('1234', $country->name);
        $country->save();

        $country = Country::where('iso', '=', 'gr')->first();
        $this->assertEquals('1234', $country->name);
    }

    public function testSavingTranslationProvidingLocale()
    {
        $country = Country::where('iso', '=', 'gr')->first();

        $country->el->name = 'abcd';
        $this->assertEquals('abcd', $country->el->name);

        $this->app->setLocale('el');
        $this->assertEquals('abcd', $country->name);
        $country->save();

        $country = Country::where('iso', '=', 'gr')->first();
        $this->assertEquals('abcd', $country->el->name);
    }

    public function testCreatingInstanceWithoutTranslation()
    {
        $country = new Country;
        $country->iso = 'be';
        $country->save();

        $country = Country::where('iso', '=', 'be')->first();
        $country->name = 'Belgium';
        $country->save();

        $country = Country::where('iso', '=', 'be')->first();
        $this->assertEquals('Belgium', $country->name);

    }

    public function testCreatingInstanceWithTranslation()
    {
        $country = new Country;
        $country->iso = 'be';
        $country->name = 'Belgium';
        $country->save();

        $country = Country::where('iso', '=', 'be')->first();
        $this->assertEquals('Belgium', $country->name);
    }

    public function testCreatingInstanceUsingMassAssignment()
    {
        $data = array(
            'iso' => 'be',
            'name' => 'Belgium',
        );
        $country = Country::create($data);
        $this->assertEquals('be', $country->iso);
        $this->assertEquals('Belgium', $country->name);
    }

    public function testCreatingInstanceUsingMassAssignmentAndLocales()
    {
        $data = array(
            'iso' => 'be',
            'en' => array('name' => 'Belgium'),
            'fr' => array('name' => 'Belgique')
        );
        $country = Country::create($data);
        $this->assertEquals('be', $country->iso);
        $this->assertEquals('Belgium', $country->en->name);
        $this->assertEquals('Belgique', $country->fr->name);

        $country = Country::where('iso', '=', 'be')->first();
        $this->assertEquals('Belgium', $country->en->name);
        $this->assertEquals('Belgique', $country->fr->name);
    }

    public function testMassAssignmentWithNonFillable()
    {
        $data = array(
            'iso' => 'be',
            'en' => array('name' => 'Belgium'),
            'fr' => array('name' => 'Belgique')
        );
        $country = CountryStrict::create($data);
        $this->assertEquals('be', $country->iso);
        $this->assertNull($country->en->name);
        $this->assertNull($country->fr->name);
    }

    public function testGettingTranslationFromSpecificLocale()
    {
        $country = Country::find(1);

        $this->assertTrue(is_object($country->en));
        $this->assertEquals('Greece', $country->en->name);
        $this->assertEquals('Ελλάδα', $country->el->name);
    }


}