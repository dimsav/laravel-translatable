<?php

use Orchestra\Testbench\TestCase;
use Dimsav\Translatable\Test\Model\Country;

class TestsBase extends TestCase {

    protected $queriesCount;

    public function setUp()
    {
        parent::setUp();
        $artisan = $this->app->make('artisan');
        $artisan->call('migrate:reset', [
            '--database' => 'mysql',
        ]);
        $artisan->call('migrate', [
            '--database' => 'mysql',
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

        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', array(
            'driver'   => 'mysql',
            'host' => 'localhost',
            'database' => 'translatable_test',
            'username' => 'homestead',
            'password' => 'secret',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
        ));
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