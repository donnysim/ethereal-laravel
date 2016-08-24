<?php

use Ethereal\Bastion\Bastion;
use Ethereal\Bastion\Clipboard;
use Ethereal\Bastion\Helper;
use Ethereal\Bastion\Sanitizer;
use Illuminate\Auth\Access\Gate;
use Orchestra\Testbench\TestCase;

class BaseTestCase extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--database' => 'ethereal',
            '--realpath' => __DIR__ . '/migrations',
        ]);

        $this->app->singleton(Clipboard::class, function () {
            return new Clipboard();
        });
    }

    /**
     * Setup testing environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'ethereal');
        $app['config']->set('database.connections.ethereal', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('bastion', [
            'tables' => [
                'assigned_roles' => 'assigned_roles',
                'abilities' => 'abilities',
                'permissions' => 'permissions',
            ],

            'models' => [
                'ability' => TestAbilityModel::class,
                'role' => TestRoleModel::class,
            ],

            'authorities' => [
                TestUserModel::class,
            ]
        ]);
    }

    protected function bastion($authority = null)
    {
        $bastion = new Bastion($this->gate($authority), Helper::clipboard(), new Sanitizer());

        return $bastion;
    }

    protected function gate($authority)
    {
        $gate = new Gate($this->app, function () use ($authority) {
            return $authority;
        });

        Helper::clipboard()->registerAt($gate);

        return $gate;
    }
}

