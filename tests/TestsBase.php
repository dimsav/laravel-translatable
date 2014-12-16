<?php

use Orchestra\Testbench\TestCase;
use Dimsav\Translatable\Test\Model\Country;

class TestsBase extends TestCase {

    protected $queriesCount;

    public function setUp()
    {

        parent::setUp();

        App::register('Dimsav\Translatable\TranslatableServiceProvider');

        $this->resetDatabase();

        $this->countQueries();
    }

    public function testRunningMigration()
    {
        $country = Country::find(1);
        $this->assertEquals('gr', $country->iso);
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['path.base'] = __DIR__ . '/../src';
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
        $app['config']->set('translatable::locales', array('el', 'en', 'fr', 'de', 'id'));
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

    private function resetDatabase()
    {
        $artisan = $this->app->make('artisan');

        // This creates the "migrations" table if not existing
        $artisan->call('migrate', [
            '--database' => 'mysql',
            '--path'     => '../tests/migrations',
        ]);

        // We empty the tables
        $artisan->call('migrate:reset', [
            '--database' => 'mysql',
        ]);
        // We fill the tables
        $artisan->call('migrate', [
            '--database' => 'mysql',
            '--path'     => '../tests/migrations',
        ]);

    }
}