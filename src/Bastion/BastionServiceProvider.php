<?php

namespace Ethereal\Bastion;

use Ethereal\Cache\TagFileStore;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations/bastion');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/bastion.php', 'bastion');

        $this->app->singleton(Store::class, function ($app) {
            $store = new Store();
            $store->setCache(new TagFileStore($app['files'], storage_path('cache/bastion')));
            return $store;
        });

        $this->app->singleton(Bastion::class, function ($app) {
            return new Bastion($app, $app->make(Store::class));
        });

        Relation::morphMap([
            'bastion-ability' => Database\Ability::class,
            'bastion-assigned-role' => Database\AssignedRole::class,
            'bastion-permission' => Database\Permission::class,
            'bastion-role' => Database\Role::class,
        ], true);
    }
}
