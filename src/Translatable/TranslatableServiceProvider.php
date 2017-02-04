<?php

namespace Dimsav\Translatable;

use Illuminate\Support\ServiceProvider;

class TranslatableServiceProvider extends ServiceProvider
{
    /**
     * Is loading deferred
     *
     * @var bool
     */
    protected $defer = true;

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/translatable.php' => config_path('translatable.php'),
        ]);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/translatable.php', 'translatable'
        );

        // Register the helper
        $this->registerTranslatableLocales();
    }

    /**
     * Register the helper TranslatableLocales class
     */
    public function registerTranslatableLocales()
    {
        $this->app->singleton('translatable.locales', function($app)
        {
            $locales = new TranslatableLocales($app->make('config'));

            return $locales;
        });
    }

    /**
     * Get the services that this provider provides.
     *
     * @return array
     */
    public function provides()
    {
        return  [
            'translatable.locales'
        ];
    }
}
