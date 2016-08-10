<?php

use Dimsav\Translatable\Test\Model\Country;
use Orchestra\Testbench\TestCase;

class TestsBase extends TestCase
{
    protected $queriesCount;

    const DB_NAME = 'translatable_test';
    const DB_USERNAME = 'homestead';
    const DB_PASSWORD = 'secret';

    public function setUp()
    {
        $this->dropDb();
        $this->createDb();

        parent::setUp();

        $this->resetDatabase();
        $this->countQueries();
    }

    private function dropDb()
    {
        $this->runQuery('DROP DATABASE IF EXISTS '.static::DB_NAME);
    }

    private function createDb()
    {
        $this->runQuery('CREATE DATABASE '.static::DB_NAME);
    }

    /**
     * @param $query
     * return void
     */
    private function runQuery($query)
    {
        $dbUsername = static::DB_USERNAME;
        $dbPassword = static::DB_PASSWORD;

        $command = "mysql -u $dbUsername ";
        $command .= $dbPassword ? " -p$dbPassword" : '';
        $command .= " -e '$query'";

        exec($command.' 2>/dev/null');
    }

    public function testRunningMigration()
    {
        $country = Country::find(1);
        $this->assertEquals('gr', $country->code);
    }

    protected function getPackageProviders($app)
    {
        return ['Dimsav\Translatable\TranslatableServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['path.base'] = __DIR__.'/..';
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver'   => 'mysql',
            'host' => 'localhost',
            'database' => static::DB_NAME,
            'username' => static::DB_USERNAME,
            'password' => static::DB_PASSWORD,
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
        ]);
        $app['config']->set('translatable.locales', ['el', 'en', 'fr', 'de', 'id']);
    }

    protected function getPackageAliases($app)
    {
        return ['Eloquent' => 'Illuminate\Database\Eloquent\Model'];
    }

    protected function countQueries()
    {
        $that = $this;
        $event = App::make('events');
        $event->listen('illuminate.query', function ($query, $bindings) use ($that) {
            $that->queriesCount++;
            $bindings = $this->formatBindingsForSqlInjection($bindings);
            $query = $this->insertBindingsIntoQuery($query, $bindings);
            $query = $this->beautifyQuery($query);
            // echo("\n--- Query {$that->queriesCount}--- $query\n");
        });
    }

    private function beautifyQuery($query)
    {
        $capitalizeWords = ['select ', ' from ', ' where ', ' on ', ' join '];
        $newLineWords = ['select ', 'from ', 'where ', 'join '];
        foreach ($capitalizeWords as $word) {
            $query = str_replace($word, strtoupper($word), $query);
        }

        foreach ($newLineWords as $word) {
            $query = str_replace($word, "\n$word", $query);
            $word = strtoupper($word);
            $query = str_replace($word, "\n$word", $query);
        }

        return $query;
    }

    private function resetDatabase()
    {
        // Relative to the testbench app folder: vendors/orchestra/testbench/src/fixture
        $migrationsPath = 'tests/migrations';
        $artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');

        // Makes sure the migrations table is created
        $artisan->call('migrate', [
            '--database' => 'mysql',
            '--path'     => $migrationsPath,
        ]);

        // We empty all tables
        $artisan->call('migrate:reset', [
            '--database' => 'mysql',
        ]);

        // Migrate
        $artisan->call('migrate', [
            '--database' => 'mysql',
            '--path'     => $migrationsPath,
        ]);
    }

    /**
     * @param $bindings
     *
     * @return mixed
     */
    private function formatBindingsForSqlInjection($bindings)
    {
        foreach ($bindings as $i => $binding) {
            if ($binding instanceof DateTime) {
                $bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
            } else {
                if (is_string($binding)) {
                    $bindings[$i] = "'$binding'";
                }
            }
        }

        return $bindings;
    }

    /**
     * @param $query
     * @param $bindings
     *
     * @return string
     */
    private function insertBindingsIntoQuery($query, $bindings)
    {
        if (empty($bindings)) {
            return $query;
        }

        $query = str_replace(['%', '?'], ['%%', '%s'], $query);

        return vsprintf($query, $bindings);
    }
}
