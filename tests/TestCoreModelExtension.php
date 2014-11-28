<?php

use Dimsav\Translatable\Test\Model\Continent;
use Dimsav\Translatable\Test\Model\Country;
use Dimsav\Translatable\Test\Model\CountryGuarded;
use Dimsav\Translatable\Test\Model\CountryStrict;
use Dimsav\Translatable\Test\Model\CountryTranslation;
use Dimsav\Translatable\Test\Model\City;
use Dimsav\Translatable\Test\Model\CityTranslation;
use Dimsav\Translatable\Test\Model\Company;
use Orchestra\Testbench\TestCase;

class TestCoreModelExtension extends TestsBase {

    // Saving

    /**
     * @test
     */
    public function it_saves_empty_instances()
    {
        $company = new Company;
        $company->save();
        $this->assertGreaterThan(0, $company->id);

        $country = new Continent;
        $country->save();
        $this->assertGreaterThan(0, $country->id);
    }

    /**
     * @test
     */
    public function it_saves_translations_when_existing_and_dirty()
    {
        $country = Country::find(1);
        $country->iso = 'make_model_dirty';
        $country->name = 'abc';
        $this->assertTrue($country->save());
        $country = Country::find(1);
        $this->assertEquals($country->name, 'abc');
    }

    // Failing saving

    /**
     * @test
     * @expectedException \Exception
     */
    public function it_throws_query_exception_if_iso_is_null()
    {
        $country = new Country();
        $country->name = 'Belgium';
        $country->iso = null;
        $country->save();
    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function it_throws_query_exception_if_saving_and_name_is_null()
    {
        $country = new Country();
        $country->iso = 'be';
        $country->name = null;
        $country->save();
    }

    /**
     * @test
     */
    public function it_returns_false_if_exists_and_dirty_and_parent_save_returns_false()
    {
        $that = $this;
        $event = App::make('events');
        $event->listen('eloquent*', function($model) use ($that) {
                return get_class($model) == 'Dimsav\Translatable\Test\Model\Country' ? false : true;
            });

        $country = Country::find(1);
        $country->iso = 'make_model_dirty';
        $country->name = 'abc';
        $this->assertFalse($country->save());
    }

    /**
     * @test
     */
    public function it_returns_false_if_does_not_exist_and_parent_save_returns_false()
    {
        $that = $this;
        $event = App::make('events');
        $event->listen('eloquent*', function($model) use ($that) {
                return get_class($model) == 'Dimsav\Translatable\Test\Model\Continent' ? false : true;
            });

        $continent = new Continent;
        $this->assertFalse($continent->save());
    }

    // Filling

    /**
     * @test
     * @expectedException Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function it_throws_exception_if_filling_a_protected_property()
    {
        $country = new CountryGuarded();
        $this->assertTrue($country->totallyGuarded());
        $country->fill(['en' => ['name' => 'Italy']]);
    }

    // Deleting

    /**
     * @test
     */
    public function it_deletes_translations()
    {
        $city = City::find(1);
        $cityId = $city->id;
        $translation = $city->translate('en');
        $this->assertTrue(is_object($translation));
        $city->delete();
        $city = City::find($cityId);
        $this->assertNull($city);
        $translations = CityTranslation::where('city_id', '=', $cityId)->get();
        $this->assertEquals(0, count($translations));
    }

    /**
     * @test
     */
    public function it_does_not_delete_translations_when_attempting_to_delete_translatable()
    {
        $country = Country::find(1);
        $countryId = $country->id;
        $translation = $country->translate('en');
        $this->assertTrue(is_object($translation));
        try {
            $country->delete();
        }
        catch (\Exception $e) {}

        $country = Country::find(1);
        $this->assertNotNull($country);

        $translations = CountryTranslation::where('country_id', '=', $countryId)->get();
        $this->assertEquals(4, count($translations));
    }

    /**
     * @test
     */
    public function it_does_not_delete_translations_while_force_deleting()
    {
        $country = CountryStrict::find(2);
        $country->forceDelete();
        $after = CountryTranslation::where('country_id', '=', 2)->get();
        $this->assertEquals(0, count($after));
    }

    /**
     * @test
     */
    public function to_array_returs_translated_attributes()
    {
        $country = Country::find(1);
        $this->assertArrayHasKey('name', $country->toArray());
        $this->assertArrayHasKey('iso', $country->toArray());
    }

    /**
     * @test
     */
    public function to_array_wont_break_if_no_translations_exist()
    {
        $country = new Country(['iso' => 'test']);
        $country->save();
        $country->toArray();
    }

    // Performance

    /**
     * @test
     */
    public function it_passes_the_N_plus_1_problem()
    {
        $countries = Country::with('translations')->get();
        foreach ($countries as $country) {
            $country->name;
        }
        $this->assertGreaterThan(2, count($countries));
        $this->assertEquals(2, $this->queriesCount);
    }


    // Forms

    /**
     * @test
     */
    public function it_fakes_isset_for_translated_attributes()
    {
        $country = Country::find(1);
        $this->assertEquals(true, isset($country->name));
    }
}