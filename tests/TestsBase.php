<?php

use Orchestra\Testbench\TestCase;
use Dimsav\Translatable\Test\Model\Country;

class TestsBase extends TestCase {

    protected $queriesCount;

    public function setUp()
    {
        parent::setUp();
        $artisan = $this->app->make('artisan');
        $artisan->call('migrate', [
            '--database' => 'testbench',
            '--path'     => '../tests/migrations',
        ]);
        $this->countQueries();
    }

    public function testRunningMigration()
    {
        $country = Country::find(1);
        $this->assertEquals('gr', $country->iso);
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['path.base'] = __DIR__ . '/../Translatable';

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', array(
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ));
        DB::statement('PRAGMA foreign_keys = ON');
        $app['config']->set('app.locale', 'en');
        $app['config']->set('app.locales', array('el', 'en', 'fr', 'de', 'id'));
        $app['config']->set('app.fallback_locale', 'de');
    }

    protected function getPackageAliases()
    {
        return array('Eloquent' => 'Illuminate\Database\Eloquent\Model');
    }

    protected function countQueries() {
        $that = $this;
        $event = App::make('events');
        $event->listen('illuminate.query', function() use ($that) {
            $that->queriesCount++;
        });
    }
}