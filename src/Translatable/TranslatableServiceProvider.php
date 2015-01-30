<?php namespace Dimsav\Translatable;

use Illuminate\Support\ServiceProvider;

class TranslatableServiceProvider extends ServiceProvider {

    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/translatable.php';
        $this->mergeConfigFrom($configPath, 'translatable');
        $this->publishes([$configPath => config_path('translatable.php')]);
    }
}
