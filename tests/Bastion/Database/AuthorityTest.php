<?php

namespace Tests\Bastion\Database;

use Ethereal\Bastion\BastionServiceProvider;
use Ethereal\Bastion\Database\AssignedPermission;
use Ethereal\Bastion\Database\AssignedRole;
use Ethereal\Bastion\Database\Permission;
use Ethereal\Bastion\Database\Role;
use Illuminate\Database\Eloquent\Relations\Relation;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Orchestra\Testbench\Traits\WithLoadMigrationsFrom;
use Tests\Models\TestUserModel;

class AuthorityTest extends TestCase
{
    use WithLoadMigrationsFrom;

    /**
     * @test
     */
    public function it_can_self_assign_permissions()
    {
        Relation::morphMap([], false);

        $authority = TestUserModel::create(['email' => 'test']);

        $authority->allow('destroy', Role::class);
        self::assertEquals('destroy', $authority->permissions()->first()->name);
        self::assertEquals(Role::class, $authority->permissions()->first()->model_type);
    }

    /**
     * @test
     */
    public function it_can_reassign_roles()
    {
        $authority = TestUserModel::create(['email' => 'test']);

        $authority->assign('admin');
        self::assertEquals('admin', $authority->roles()->first()->name);

        $authority->reassign('developer');
        self::assertEquals('developer', $authority->roles()->first()->name);
    }

    /**
     * @test
     */
    public function it_can_self_assign_a_role()
    {
        $authority = TestUserModel::create(['email' => 'test']);
        $authority->assign('admin');

        self::assertEquals('admin', $authority->roles()->first()->name);
    }

    /**
     * @test
     */
    public function it_has_permissions_relation()
    {
        Relation::morphMap([], false);

        $authority = TestUserModel::create(['email' => 'test']);
        $permission = Permission::create(['name' => 'test']);
        AssignedPermission::create(['permission_id' => $permission->getKey(), 'model_id' => $authority->getKey(), 'model_type' => TestUserModel::class]);

        static::assertEquals(1, $authority->permissions()->count());
    }

    /**
     * @test
     */
    public function it_has_roles_relation()
    {
        Relation::morphMap([], false);

        $authority = TestUserModel::create(['email' => 'test']);
        $role = Role::create([
            'name' => 'test',
        ]);
        AssignedRole::create(['role_id' => $role->getKey(), 'model_id' => $authority->getKey(), 'model_type' => TestUserModel::class]);

        static::assertEquals(1, $authority->roles()->count());
    }

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [ConsoleServiceProvider::class, BastionServiceProvider::class];
    }

    /**
     * Setup the test environment.
     *
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../../../migrations/bastion');
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
    }
}
