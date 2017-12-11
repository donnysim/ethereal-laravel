<?php

namespace Tests\Bastion\Database;

use Ethereal\Bastion\Database\AssignedPermission;
use Ethereal\Bastion\Database\Permission;
use Ethereal\Bastion\Database\Role;
use Ethereal\Database\Ethereal;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Orchestra\Testbench\Traits\WithLoadMigrationsFrom;
use Tests\Models\TestUserModel;

class PermissionTest extends TestCase
{
    use WithLoadMigrationsFrom;

    /**
     * @test
     * @expectedException \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public function authority_must_exist_to_be_assigned_to()
    {
        $authority = new TestUserModel(['email' => 'john@doe.com']);
        $permission = Permission::createPermission('assign');
        $permission->assignTo($authority);
    }

    /**
     * @test
     * @expectedException \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public function authority_must_exist_to_get_permissions()
    {
        Permission::ofAuthority(new Ethereal());
    }

    /**
     * @test
     * @expectedException \Ethereal\Bastion\Exceptions\InvalidPermissionException
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     */
    public function ensure_permission_model_exists()
    {
        Permission::ensurePermissions([new Permission()]);
    }

    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     */
    public function ensure_permission_string_creates_it_if_not_found()
    {
        $result = Permission::ensurePermissions(['create-by-name']);

        self::assertEquals(['create-by-name'], $result->pluck('name')->all());
    }

    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     */
    public function ensure_permission_string_gets_permission_by_name()
    {
        Permission::create(['name' => 'by-name']);

        $result = Permission::ensurePermissions(['by-name']);

        self::assertEquals(['by-name'], $result->pluck('name')->all());
    }

    /**
     * @test
     */
    public function it_can_be_assigned_to_authority()
    {
        $authority = TestUserModel::create(['email' => 'john@doe.com']);
        $permission = Permission::createPermission('assign');
        $permission->assignTo($authority);

        self::assertTrue(AssignedPermission::where([
            'permission_id' => $permission->getKey(),
            'model_id' => $authority->getKey(),
            'model_type' => $authority->getMorphClass(),
        ])->exists());
    }

    /**
     * @test
     */
    public function it_can_be_assigned_to_authority_as_forbidden()
    {
        $authority = TestUserModel::create(['email' => 'john@doe.com']);
        $permission = Permission::createPermission('assign');
        $permission->assignTo($authority);

        self::assertTrue(AssignedPermission::where([
            'permission_id' => $permission->getKey(),
            'model_id' => $authority->getKey(),
            'model_type' => $authority->getMorphClass(),
        ])->exists());
    }

    /**
     * @test
     */
    public function it_can_create_permission()
    {
        $permission = Permission::createPermission('created', 'test', 10);

        self::assertTrue($permission->exists);
        self::assertEquals('created', $permission->name);
        self::assertEquals('test', $permission->model_type);
        self::assertEquals(10, $permission->model_id);
    }

    /**
     * @test
     */
    public function it_can_find_permission_by_name()
    {
        Permission::create(['name' => 'name']);

        self::assertEquals('name', Permission::findPermission('name')->name);
    }

    /**
     * @test
     */
    public function it_can_find_permission_by_name_and_model()
    {
        Permission::create(['name' => 'name-guard-model', 'model_id' => 1, 'model_type' => 'test']);
        Permission::create(['name' => 'name-guard-model', 'model_id' => null, 'model_type' => 'test']);

        $permission = Permission::findPermission('name-guard-model', 'test');

        self::assertNull($permission->model_id);
        self::assertEquals('test', $permission->model_type);
    }

    /**
     * @test
     */
    public function it_can_find_permission_by_name_model_and_id()
    {
        Permission::create(['name' => 'name-guard-model-id', 'model_id' => null, 'model_type' => 'test']);
        Permission::create(['name' => 'name-guard-model-id', 'model_id' => 1, 'model_type' => 'test']);

        $permission = Permission::findPermission('name-guard-model-id', 'test', 1);

        self::assertEquals(1, $permission->model_id);
        self::assertEquals('test', $permission->model_type);
    }

    /**
     * @test
     */
    public function it_generates_correct_identifier()
    {
        $permission = new Permission(['name' => 'edit']);
        self::assertEquals('edit', $permission->identifier);

        $permission = new Permission(['name' => 'edit', 'model_type' => '*']);
        self::assertEquals('edit-*', $permission->identifier);

        $permission = new Permission(['name' => 'edit', 'model_type' => 'user', 'model_id' => '1']);
        self::assertEquals('edit-user-1', $permission->identifier);
    }

    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public function it_gets_authority_permissions_including_role_assigned()
    {
        $authority = TestUserModel::create(['email' => 'john@doe.com']);
        $role = Role::create(['name' => 'role']);
        Permission::createPermission('authority')->assignTo($authority);
        $role->assignTo($authority);
        Permission::createPermission('role')->assignTo($role);

        self::assertEquals(['authority', 'role'], Permission::ofAuthority($authority, collect([$role]))->pluck('name')->all());
    }

    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public function it_gets_authority_permissions_with_forbid_attribute()
    {
        $authority = TestUserModel::create(['email' => 'john@doe.com']);
        Permission::createPermission('allow')->assignTo($authority, false);
        Permission::createPermission('forbid')->assignTo($authority, true);

        $permissions = Permission::ofAuthority($authority);

        self::assertFalse($permissions->first(function ($p) {
            return $p->name === 'allow';
        })->forbid);
        self::assertTrue($permissions->first(function ($p) {
            return $p->name === 'forbid';
        })->forbid);
    }

    /**
     * @test
     * @expectedException \Ethereal\Bastion\Exceptions\InvalidPermissionException
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public function permission_must_exist_to_be_assigned_to()
    {
        $authority = TestUserModel::create(['email' => 'john@doe.com']);
        $permission = new Permission();
        $permission->assignTo($authority);
    }

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
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
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
    }
}
