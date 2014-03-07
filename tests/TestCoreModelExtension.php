<?php

use Dimsav\Translatable\Test\Model\Country;
use Dimsav\Translatable\Test\Model\CountryGuarded;
use Dimsav\Translatable\Test\Model\CountryStrict;
use Dimsav\Translatable\Test\Model\CountryTranslation;
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