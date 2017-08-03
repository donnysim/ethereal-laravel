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
        $this->registerBladeDirectives();
    }

    /**
     * Register blade directives.
     */
    protected function registerBladeDirectives()
    {
        /** @var \Illuminate\View\Compilers\BladeCompiler $compiler */
        $compiler = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $compiler->directive('can', function ($expression) {
            return "<?php if (auth()->check() && auth()->user()->can({$expression})) : ?>";
        });

        $compiler->directive('cannot', function ($expression) {
            return "<?php if (auth()->check() && auth()->user()->cannot({$expression})) : ?>";
        });

        $compiler->directive('is', function ($expression) {
            return "<?php if (auth()->check() && auth()->user()->isA({$expression})) : ?>";
        });

        $compiler->directive('isnot', function ($expression) {
            return "<?php if (auth()->check() && auth()->user()->isNotA({$expression})) : ?>";
        });

        $compiler->directive('allowed', function ($expression) {
            return "<?php if (auth()->check() && auth()->user()->allowed({$expression})) : ?>";
        });

        $compiler->directive('denied', function ($expression) {
            return "<?php if (auth()->check() && auth()->user()->denied({$expression})) : ?>";
        });

        $compiler->directive('rolewith', function ($expression) {
            return "<?php if (auth()->check() && auth()->user()->hasRoleWith({$expression})) : ?>";
        });

        $compiler->directive('endcan', function ($expression) {
            return '<?php endif; ?>';
        });

        $compiler->directive('endcannot', function ($expression) {
            return '<?php endif; ?>';
        });

        $compiler->directive('endis', function ($expression) {
            return '<?php endif; ?>';
        });

        $compiler->directive('endisnot', function ($expression) {
            return '<?php endif; ?>';
        });

        $compiler->directive('endallowed', function ($expression) {
            return '<?php endif; ?>';
        });

        $compiler->directive('enddenied', function ($expression) {
            return '<?php endif; ?>';
        });

        $compiler->directive('endrolewith', function ($expression) {
            return '<?php endif; ?>';
        });
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
            'bastion-ability' => Helper::getAbilityModelClass(),
            'bastion-assigned-role' => Helper::getAssignedRoleModelClass(),
            'bastion-permission' => Helper::getPermissionModelClass(),
            'bastion-role' => Helper::getRoleModelClass(),
        ]);
    }
}
