<?php

use Dimsav\Translatable\Test\Model\Country;
use Dimsav\Translatable\Test\Model\CountryGuarded;
use Dimsav\Translatable\Test\Model\CountryStrict;
use Dimsav\Translatable\Test\Model\CountryTranslation;
use Dimsav\Translatable\Test\Model\City;
use Dimsav\Translatable\Test\Model\CityTranslation;
use Orchestra\Testbench\TestCase;

class TestCoreModelExtension extends TestsBase {

    // Failing saving

    /**
     * @expectedException \Exception
     */
    public function testSaveTranslatableThrowsException()
    {
        $country = new Country();
        $country->name = 'Belgium';
        $country->save();
    }

    /**
     * @expectedException \Exception
     */
    public function testSaveTranslationThrowsException()
    {
        $country = new Country();
        $country->iso = 'be';
        $country->name = null;
        $country->save();
    }

    public function testParentReturnsFalseOnSave()
    {
        $that = $this;
        $event = App::make('events');
        $event->listen('eloquent*', function($model) use ($that) {
                return get_class($model) == 'Dimsav\Translatable\Test\Model\Country' ? false : true;
            });

        $country = Country::find(1);
        $country->name = 'abc';
        $this->assertFalse($country->save());
    }

    // Filling

    /**
     * @expectedException \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function testExceptionIsThrownWhenModelTotallyGuarded()
    {
        $country = new CountryGuarded();
        $this->assertTrue($country->totallyGuarded());
        $country->fill(['en' => ['name' => 'Italy']]);
    }

    // Deleting

    public function testDeleting()
    {
        $city = City::find(1);
        $cityId = $city->id;
        $translation = $city->en;
        $this->assertTrue(is_object($translation));
        $city->delete();
        $city = City::find($cityId);
        $this->assertNull($city);
        $translations = CityTranslation::where('country_id', '=', $cityId)->get();
        $this->assertEquals(0, count($translations));
    }

    public function testDeletingWithConstraint()
    {
        $country = Country::find(1);
        $countryId = $country->id;
        $translation = $country->en;
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

    public function testDeletingWithSoftDeleteDoesNotDeleteTranslations()
    {
        $country = CountryStrict::find(1);
        $before = CountryTranslation::where('country_id', '=', 1)->get();
        $country->delete();

        $after = CountryTranslation::where('country_id', '=', 1)->get();
        $this->assertEquals(count($before), count($after));
    }

    public function testForceDeletingWithSoftDeleteDoesDeleteTranslations()
    {
        $country = CountryStrict::find(2);
        $country->forceDelete();
        $after = CountryTranslation::where('country_id', '=', 2)->get();
        $this->assertEquals(0, count($after));
    }

    // Performance

    public function testNPlusOne()
    {
        $countries = Country::with('translations')->get();
        foreach ($countries as $country) {
            $country->name;
        }
        $this->assertGreaterThan(2, count($countries));
        $this->assertEquals(2, $this->queriesCount);
    }
}