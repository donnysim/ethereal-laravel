<?php

namespace Tests\Bastion\Database;

use Ethereal\Bastion\Database\AssignedPermission;
use Ethereal\Bastion\Database\AssignedRole;
use Ethereal\Bastion\Database\Permission;
use Ethereal\Bastion\Database\Role;
use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Relations\Relation;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Orchestra\Testbench\Traits\WithLoadMigrationsFrom;

class PermissionTest extends TestCase
{
    use WithLoadMigrationsFrom;

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function authority_must_exist_to_get_permissions()
    {
        Permission::ofAuthority(new Ethereal());
    }

    /**
     * @test
     */
    public function it_gets_only_model_permissions_of_authority()
    {
        $permission = Permission::create([
            'name' => 'test',
            'guard' => 'default',
        ]);

        $permission2 = Permission::create([
            'name' => 'test',
            'guard' => 'next',
        ]);

        AssignedPermission::create(['permission_id' => $permission->getKey(), 'model_id' => 1, 'model_type' => Ethereal::class]);
        AssignedPermission::create(['permission_id' => $permission2->getKey(), 'model_id' => 1, 'model_type' => Ethereal::class]);

        $authority = new Ethereal(['id' => 1]);
        $authority->exists = true;
        self::assertEquals(2, Permission::ofAuthority($authority)->count());
        self::assertEquals(1, Permission::ofAuthority($authority, null, 'default')->count());

        $authority = new Ethereal(['id' => 2]);
        $authority->exists = true;
        self::assertEquals(0, Permission::ofAuthority($authority)->count());
    }

    /**
     * @test
     */
    public function it_gets_roles_and_model_permissions_of_authority()
    {
        Relation::morphMap([], false);

        $role = Role::create([
            'name' => 'test',
            'guard' => 'default',
            'system' => true,
            'private' => true,
        ]);

        $permission = Permission::create([
            'name' => 'test',
            'guard' => 'default',
        ]);

        $permission2 = Permission::create([
            'name' => 'test',
            'guard' => 'next',
        ]);

        $permission3 = Permission::create([
            'name' => 'test 2',
            'guard' => 'next',
        ]);

        AssignedRole::create(['role_id' => $role->getKey(), 'model_id' => 1, 'model_type' => Ethereal::class]);
        AssignedPermission::create(['permission_id' => $permission->getKey(), 'model_id' => 1, 'model_type' => Ethereal::class]);
        AssignedPermission::create(['permission_id' => $permission2->getKey(), 'model_id' => 1, 'model_type' => Ethereal::class]);
        AssignedPermission::create(['permission_id' => $permission3->getKey(), 'model_id' => $role->getKey(), 'model_type' => Role::class]);

        $authority = new Ethereal(['id' => 1]);
        $authority->exists = true;
        self::assertEquals(3, Permission::ofAuthority($authority, \collect([$role]))->count());
        self::assertEquals(1, Permission::ofAuthority($authority, \collect([$role]), 'default')->count());
        self::assertEquals(2, Permission::ofAuthority($authority, \collect([$role]), 'next')->count());
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
        return [ConsoleServiceProvider::class];
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
    }
}
