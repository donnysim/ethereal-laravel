<?php

namespace Ethereal\Bastion;

use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\ServiceProvider;

class BastionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerClipboard();
        $this->registerBastion();
        $this->registerSanitizer();
    }

    public function boot()
    {
        $this->registerAtGate();
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
                $this->app->make(Clipboard::class),
                $this->app->make(Sanitizer::class)
            );

            return $bastion;
        });
    }

    protected function registerAtGate()
    {
        $gate = $this->app->make(Gate::class);
        $clipboard = $this->app->make(Clipboard::class);
        $clipboard->registerAt($gate);
    }

    protected function registerSanitizer()
    {
        $this->app->singleton(Sanitizer::class, function () {
            return new Sanitizer();
        });
    }
}