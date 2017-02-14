<?php

namespace Ethereal\Bastion;

use Ethereal\Cache\TagFileStore;
use Illuminate\Support\ServiceProvider;

class BastionServiceProvider extends ServiceProvider
{
    /**
     * Boot the application services.
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../../config/bastion.php';
        $this->publishes([$configPath => config_path('bastion.php')], 'config');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $configPath = __DIR__ . '/../../config/bastion.php';
        $this->mergeConfigFrom($configPath, 'bastion');

        $this->app->singleton(Store::class, function ($app) {
            $store = new Store();
            $store->setCache(new TagFileStore($app['files'], storage_path('cache/bastion')));
            return $store;
        });

        $this->app->singleton(Bastion::class, function ($app) {
            return new Bastion($app, $app->make(Store::class));
        });
    }
}
