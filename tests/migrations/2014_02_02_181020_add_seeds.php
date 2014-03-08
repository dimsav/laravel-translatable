<?php

use Illuminate\Database\Migrations\Migration;
use Dimsav\Translatable\Test\Model\Country;
use Dimsav\Translatable\Test\Model\CountryTranslation;
use Dimsav\Translatable\Test\Model\City;
use Dimsav\Translatable\Test\Model\CityTranslation;

class AddSeeds extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        $countries = array(
            ['id'=>1, 'iso'=>'gr'],
            ['id'=>2, 'iso'=>'fr'],
            ['id'=>3, 'iso'=>'en'],
            ['id'=>4, 'iso'=>'de'],
        );

        $this->createCountries($countries);

        $countryTranslations = array(
            ['country_id' => 1, 'locale' => 'el', 'name' => 'Ελλάδα'],
            ['country_id' => 1, 'locale' => 'fr', 'name' => 'Grèce'],
            ['country_id' => 1, 'locale' => 'en', 'name' => 'Greece'],
            ['country_id' => 1, 'locale' => 'de', 'name' => 'Griechenland'],
            ['country_id' => 2, 'locale' => 'en', 'name' => 'France'],
        );

        $this->createCountryTranslations($countryTranslations);


        $cities = array(
            ['id'=>1, 'country_id'=>1],
        );

        $this->createCities($cities);

        $cityTranslations = array(
            ['city_id' => 1, 'locale' => 'el', 'name' => 'Αθήνα'],
            ['city_id' => 1, 'locale' => 'fr', 'name' => 'Athènes'],
            ['city_id' => 1, 'locale' => 'en', 'name' => 'Athens'],
            ['city_id' => 1, 'locale' => 'de', 'name' => 'Athen'],
        );

        $this->createCityTranslations($cityTranslations);
	}

    private function createCountries($countries)
    {
        foreach ($countries as $data) {
            $country = new Country;
            $country->id = $data['id'];
            $country->iso = $data['iso'];
            $country->save();
        }
    }

    private function createCountryTranslations($translations)
    {
        foreach ($translations as $data) {
            $translation = new CountryTranslation;
            $translation->country_id = $data['country_id'];
            $translation->locale = $data['locale'];
            $translation->name = $data['name'];
            $translation->save();
        }
    }

    private function createCities($cities)
    {
        foreach ($cities as $data) {
            $city = new City;
            $city->id = $data['id'];
            $city->country_id = $data['country_id'];
            $city->save();
        }
    }

    private function createCityTranslations($translations)
    {
        foreach ($translations as $data) {
            $translation = new CityTranslation;
            $translation->city_id = $data['city_id'];
            $translation->locale = $data['locale'];
            $translation->name = $data['name'];
            $translation->save();
        }
    }

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {}

}
