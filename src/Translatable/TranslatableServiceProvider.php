<?php namespace Dimsav\Translatable;

use Illuminate\Support\ServiceProvider;

class TranslatableServiceProvider extends ServiceProvider {

    public function boot()
    {
        $this->publishes([
            __DIR__ .'/../'=> config_path('translatable'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }
}
