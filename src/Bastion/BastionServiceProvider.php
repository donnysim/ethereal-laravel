<?php

namespace Ethereal\Bastion;

use Ethereal\Bastion\Store\Store;
use Ethereal\Cache\GroupFileStore;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\ServiceProvider;

class BastionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerStore();
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
     */
    protected function registerBastion()
    {
        $this->app->singleton(Bastion::class, function () {
            $bastion = new Bastion(
                $this->app->make(GateContract::class),
                $this->app->make(Store::class)
            );

            return $bastion;
        });
    }

    /**
     * Register bastion at gate.
     */
    public function boot()
    {
        $this->registerAtGate();
    }

    /**
     * Register bastion at gate.
     */
    protected function registerAtGate()
    {
        $store = $this->app->make(Store::class);
        $store->registerAt($this->app->make(GateContract::class));
    }

    /**
     * Register the access gate service.
     *
     * @return void
     */
    protected function registerAccessGate()
    {
        $this->app->singleton(GateContract::class, function ($app) {
            return new Gate($app, function () use ($app) {
                return call_user_func($app['auth']->userResolver());
            });
        });
    }
}
