<?php namespace Dimsav\Translatable;

use Illuminate\Support\ServiceProvider;

class TranslatableServiceProvider extends ServiceProvider {

    public function boot()
    {
        $this->package('dimsav/laravel-translatable', 'translatable', __DIR__ .'/../');
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
