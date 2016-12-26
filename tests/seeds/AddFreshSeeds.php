<?php

use Dimsav\Translatable\Test\Model\City;
use Dimsav\Translatable\Test\Model\Country;
use Dimsav\Translatable\Test\Model\CityTranslation;
use Dimsav\Translatable\Test\Model\CountryTranslation;

class AddFreshSeeds
{
    public function run()
    {
        $countries = [
            ['id' => 1, 'code' => 'gr'],
            ['id' => 2, 'code' => 'fr'],
            ['id' => 3, 'code' => 'en'],
            ['id' => 4, 'code' => 'de'],
        ];

        $this->createCountries($countries);

        $countryTranslations = [
            ['country_id' => 1, 'locale' => 'el', 'name' => 'Ελλάδα'],
            ['country_id' => 1, 'locale' => 'fr', 'name' => 'Grèce'],
            ['country_id' => 1, 'locale' => 'en', 'name' => 'Greece'],
            ['country_id' => 1, 'locale' => 'de', 'name' => 'Griechenland'],
            ['country_id' => 2, 'locale' => 'en', 'name' => 'France'],
        ];

        $this->createCountryTranslations($countryTranslations);

        $cities = [
            ['id' => 1, 'country_id' => 1],
        ];

        $this->createCities($cities);

        $cityTranslations = [
            ['city_id' => 1, 'locale' => 'el', 'name' => 'Αθήνα'],
            ['city_id' => 1, 'locale' => 'fr', 'name' => 'Athènes'],
            ['city_id' => 1, 'locale' => 'en', 'name' => 'Athens'],
            ['city_id' => 1, 'locale' => 'de', 'name' => 'Athen'],
        ];

        $this->createCityTranslations($cityTranslations);
    }

    private function createCountries($countries)
    {
        foreach ($countries as $data) {
            $country = new Country();
            $country->id = $data['id'];
            $country->code = $data['code'];
            $country->save();
        }
    }

    private function createCountryTranslations($translations)
    {
        foreach ($translations as $data) {
            $translation = new CountryTranslation();
            $translation->country_id = $data['country_id'];
            $translation->locale = $data['locale'];
            $translation->name = $data['name'];
            $translation->save();
        }
    }

    private function createCities($cities)
    {
        foreach ($cities as $data) {
            $city = new City();
            $city->id = $data['id'];
            $city->country_id = $data['country_id'];
            $city->save();
        }
    }

    private function createCityTranslations($translations)
    {
        foreach ($translations as $data) {
            $translation = new CityTranslation();
            $translation->city_id = $data['city_id'];
            $translation->locale = $data['locale'];
            $translation->name = $data['name'];
            $translation->save();
        }
    }
}
