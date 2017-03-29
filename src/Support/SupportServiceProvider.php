<?php

namespace Ethereal\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;

class SupportServiceProvider extends ServiceProvider
{
    /**
     * Register support service helpers.
     */
    public function register()
    {
        $this->registerBuilderThrough();
    }

    /**
     * Register query Builder through macro.
     */
    protected function registerBuilderThrough()
    {
        Builder::macro('through', function ($callable, array $args = []) {
            array_unshift($args, $this);
            $callable(...$args);
        });
    }
}
