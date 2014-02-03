<?php

use Orchestra\Testbench\TestCase;
use Dimsav\Translatable\Test\Model\Country;

class TestCoreModelExtension extends TestsBase {

    /**
     * @expectedException Illuminate\Database\QueryException
     */
    public function testSaveTranslatableThrowsException() {
        $country = new Country();
        $country->name = 'Belgium';
        $country->save();
    }

    /**
     * @expectedException Illuminate\Database\QueryException
     */
    public function testSaveTranslationThrowsException() {
        $country = new Country();
        $country->iso = 'Belgium';
        $country->name = null;
        $country->save();
    }


} 