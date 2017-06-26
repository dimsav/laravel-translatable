<?php

namespace Dimsav\Translatable;

use Dimsav\Translatable\Console\MigrationCreator;
use Dimsav\Translatable\Console\TranslateTableCommand;
use Illuminate\Foundation\Providers\ArtisanServiceProvider;

class TranslatableArtisanProvider extends ArtisanServiceProvider
{
    public function __construct($app)
    {
        parent::__construct($app);
        $this->devCommands = array_merge($this->devCommands, [
            'TranslateTable' => 'command.translate.table',
        ]);
    }

    public function register()
    {
        parent::register();
        $this->app->singleton('translate.creator', function ($app) {
            return new MigrationCreator($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerTranslateTableCommand()
    {
        $this->app->singleton('command.translate.table', function ($app) {
            // Once we have the migration creator registered, we will create the command
            // and inject the creator. The creator is responsible for the actual file
            // creation of the migrations, and may be extended by these developers.
            $creator = $app['translate.creator'];

            $composer = $app['composer'];

            return new TranslateTableCommand($creator, $composer);
        });
    }

    /**
     * Register the migration creator.
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->app->singleton('migration.creator', function ($app) {
            return new MigrationCreator($app['files']);
        });
    }
}
