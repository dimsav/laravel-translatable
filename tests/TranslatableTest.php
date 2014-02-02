<?php

use Dimsav\Translatable\Test\Model\Country;

class TranslatableTests extends TestsBase {

    public function testTranslationModelName() {
        $country = new Country();
        $this->assertEquals(
            'Dimsav\Translatable\Test\Model\CountryTranslation',
            $country->getTranslationModelNameDefault());
    }

    public function testTranslationModelCustomName() {
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




} 