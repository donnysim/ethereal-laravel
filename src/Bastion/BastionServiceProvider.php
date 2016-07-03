<?php

namespace Ethereal\Bastion;

use Illuminate\Cache\ArrayStore;
use Illuminate\Support\ServiceProvider;

class BastionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerClipboard();
        $this->registerBastion();
    }

    protected function registerClipboard()
    {
        $this->app->singleton(Clipboard::class, function () {
            return new CachedClipboard(new ArrayStore);
        });
    }

    protected function registerBastion()
    {
        $this->app->singleton(Bastion::class, function () {
            $bastion = new Bastion(
                $this->app->make(Gate::class),
                $this->app->make(Clipboard::class)
            );

            return $bastion;
        });
    }
}