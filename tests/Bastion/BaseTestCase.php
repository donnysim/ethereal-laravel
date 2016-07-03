<?php

use Ethereal\Bastion\Bastion;
use Ethereal\Bastion\Clipboard;
use Ethereal\Bastion\Helper;
use Illuminate\Auth\Access\Gate;
use Orchestra\Testbench\TestCase;

class BaseTestCase extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--database' => 'ethereal',
            '--realpath' => __DIR__ . '/../migrations',
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
                'ability' => Ability::class,
                'role' => Role::class,
            ],

            'authorities' => [
                User::class,
            ]
        ]);
    }

    protected function bastion($authority = null)
    {
        $bastion = new Bastion($this->gate($authority), Helper::clipboard());

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

class Role extends \Illuminate\Database\Eloquent\Model
{
    use \Ethereal\Bastion\Traits\Role, \Ethereal\Bastion\Traits\HasAbilities;

    protected $table = 'roles';

    protected $guarded = [];
}

class Ability extends \Illuminate\Database\Eloquent\Model
{
    use \Ethereal\Bastion\Traits\Ability;

    protected $table = 'abilities';

    protected $guarded = [];
}

class User extends \Illuminate\Database\Eloquent\Model
{
    use \Ethereal\Bastion\Traits\HasRoles, \Ethereal\Bastion\Traits\HasAbilities;

    protected $table = 'users';

    protected $guarded = [];
}

class Profile extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'profiles';

    protected $guarded = [];
}


