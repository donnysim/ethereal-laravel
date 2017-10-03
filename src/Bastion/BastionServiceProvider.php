<?php

namespace Ethereal\Bastion;

use Illuminate\Cache\FileStore;
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
        $this->publishes([
            $configPath => \config_path('bastion.php'),
            __DIR__ . '/../../migrations/bastion' => \database_path('migrations'),
        ], 'bastion');
        //        $this->registerBladeDirectives();
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/bastion.php', 'bastion');

        $this->app->singleton('bastion', function ($app) {
            return new Bastion($app, new FileStore($app['files'], \storage_path('cache/bastion')));
        });

        Relation::morphMap([
            'bastion-permission' => Helper::getPermissionModelClass(),
            'bastion-assigned-role' => Helper::getAssignedRoleModelClass(),
            'bastion-assigned-permission' => Helper::getAssignedPermissionModelClass(),
            'bastion-role' => Helper::getRoleModelClass(),
        ]);
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
}
