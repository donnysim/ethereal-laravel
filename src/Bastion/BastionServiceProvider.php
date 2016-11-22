<?php

namespace Ethereal\Bastion;

use Ethereal\Bastion\Rucks;
use Ethereal\Bastion\Store\Store;
use Ethereal\Cache\GroupFileStore;
use Illuminate\Support\ServiceProvider;

class BastionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @throws \InvalidArgumentException
     */
    public function register()
    {
        $this->registerStore();
        $this->registerRucks();
        $this->registerBastion();
    }

    /**
     * Register singleton Store instance.
     */
    protected function registerStore()
    {
        $this->app->singleton(Store::class, function () {
            return new Store(new GroupFileStore($this->app['files'], storage_path('cache/bastion')));
        });
    }

    /**
     * Register bastion instance.
     *
     * @throws \InvalidArgumentException
     */
    protected function registerBastion()
    {
        $this->app->singleton(Bastion::class, function () {
            $bastion = new Bastion(
                $this->app->make(Rucks::class),
                $this->app->make(Store::class)
            );

            $bastion->getStore()->registerAt($bastion->getRucks());

            return $bastion;
        });
    }

    /**
     * Register bastion at rucks.
     */
    public function boot()
    {
        $this->registerAtRucks();
    }

    /**
     * Register bastion at rucks.
     */
    protected function registerAtRucks()
    {
        $store = $this->app->make(Store::class);
        $store->registerAt($this->app->make(Rucks::class));
    }

    /**
     * Register the access rucks service.
     */
    protected function registerRucks()
    {
        $this->app->singleton(Rucks::class, function ($app) {
            return new Rucks($app, function () use ($app) {
                return call_user_func($app['auth']->userResolver());
            });
        });
    }
}
