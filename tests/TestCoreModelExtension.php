<?php

use Dimsav\Translatable\Test\Model;
use Dimsav\Translatable\Test\Model\City;
use Dimsav\Translatable\Test\Model\Company;
use Dimsav\Translatable\Test\Model\Country;
use Dimsav\Translatable\Test\Model\Continent;
use Dimsav\Translatable\Test\Model\Vegetable;
use Dimsav\Translatable\Test\Model\CountryStrict;
use Dimsav\Translatable\Test\Model\CountryGuarded;
use Dimsav\Translatable\Test\Model\CityTranslation;
use Dimsav\Translatable\Test\Model\CountryTranslation;

class TestCoreModelExtension extends TestsBase
{
    // Saving

    public function test_it_saves_empty_instances()
    {
        $company = new Company();
        $company->save();
        $this->assertGreaterThan(0, $company->id);

        $country = new Continent();
        $country->save();
        $this->assertGreaterThan(0, $country->id);
    }

    public function test_it_saves_translations_when_existing_and_dirty()
    {
        $country = Country::find(1);
        $country->code = 'make_model_dirty';
        $country->name = 'abc';
        $this->assertTrue($country->save());
        $country = Country::find(1);
        $this->assertEquals($country->name, 'abc');
    }

    // Failing saving

    /**
     * @expectedException \Exception
     */
    public function test_it_throws_query_exception_if_code_is_null()
    {
        $country = new Country();
        $country->name = 'Belgium';
        $country->code = null;
        $country->save();
    }

    /**
     * @expectedException \Exception
     */
    public function test_it_throws_query_exception_if_saving_and_name_is_null()
    {
        $country = new Country();
        $country->code = 'be';
        $country->name = null;
        $country->save();
    }

    public function test_it_returns_false_if_exists_and_dirty_and_parent_save_returns_false()
    {
        $that = $this;
        $event = App::make('events');
        $event->listen('eloquent*', function ($event, $models) use ($that) {
            return get_class(reset($models)) == 'Dimsav\Translatable\Test\Model\Country' ? false : true;
        });

        $country = Country::find(1);
        $country->code = 'make_model_dirty';
        $country->name = 'abc';
        $this->assertFalse($country->save());
    }

    public function test_it_returns_false_if_does_not_exist_and_parent_save_returns_false()
    {
        $that = $this;
        $event = App::make('events');
        $event->listen('eloquent*', function ($event, $models) use ($that) {
            return get_class(reset($models)) == 'Dimsav\Translatable\Test\Model\Continent' ? false : true;
        });

        $continent = new Continent();
        $this->assertFalse($continent->save());
    }

    // Filling

    /**
     * @expectedException Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function test_it_throws_exception_if_filling_a_protected_property()
    {
        $country = new CountryGuarded();
        $this->assertTrue($country->totallyGuarded());
        $country->fill(['code' => 'it', 'en' => ['name' => 'Italy']]);
    }

    /**
     * @expectedException Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function test_translation_throws_exception_if_filling_a_protected_property()
    {
        $country = new Country();
        $country->translationModel = Model\CountryTranslationGuarded::class;
        $country->fill(['code' => 'it', 'en' => ['name' => 'Italy']]);
    }

    // Deleting

    public function test_it_deletes_translations()
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

    public function test_it_does_not_delete_translations_when_attempting_to_delete_translatable()
    {
        $country = Country::find(1);
        $countryId = $country->id;
        $translation = $country->translate('en');
        $this->assertTrue(is_object($translation));
        try {
            $country->delete();
        } catch (\Exception $e) {
        }

        $country = Country::find(1);
        $this->assertNotNull($country);

        $translations = CountryTranslation::where('country_id', '=', $countryId)->get();
        $this->assertEquals(4, count($translations));
    }

    public function test_it_does_not_delete_translations_while_force_deleting()
    {
        $country = CountryStrict::find(2);
        $country->forceDelete();
        $after = CountryTranslation::where('country_id', '=', 2)->get();
        $this->assertEquals(0, count($after));
    }

    public function test_to_array_returns_translated_attributes()
    {
        $country = Country::find(1);
        $this->assertArrayHasKey('name', $country->toArray());
        $this->assertArrayHasKey('code', $country->toArray());
    }

    public function test_to_array_wont_break_if_no_translations_exist()
    {
        $country = new Country(['code' => 'test']);
        $country->save();
        $this->assertArrayHasKey('code', $country->toArray());
    }

    // Forms

    public function test_it_fakes_isset_for_translated_attributes()
    {
        $country = Country::find(1);
        $this->assertEquals(true, isset($country->name));
    }

    // Hidden attributes

    public function test_it_should_hide_attributes_after_to_array()
    {
        $country = Country::find(1);

        $this->assertEquals(true, isset($country->toArray()['name']));

        // it is equivalent to set
        //      protected $hidden = ['name'];
        // in Eloquent
        $country->setHidden(['name']);
        $this->assertEquals(false, isset($country->toArray()['name']));
    }

    public function test_it_finds_custom_primary_keys()
    {
        $vegetable = new Vegetable;
        $this->assertSame('vegetable_identity', $vegetable->getRelationKey());
    }

    public function test_setAttribute_returns_this()
    {
        $country = new Country;
        $this->assertSame($country, $country->setAttribute('code', 'ch'));
        $this->assertSame($country, $country->setAttribute('name', 'China'));
    }
}
