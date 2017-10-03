<?php

namespace Tests\Bastion\Conductors;

use Ethereal\Bastion\Conductors\AssignsPermissions;
use Ethereal\Bastion\Database\Permission;
use Ethereal\Bastion\Store;
use Illuminate\Database\Eloquent\Relations\Relation;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUserModel;

class AssignsPermissionsTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_give_action_permission()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $allow = new AssignsPermissions(new Store('default'), [$user]);
        $allow->to(['edit']);

        self::assertEquals(1, Permission::ofAuthority($user)->count());
    }

    /**
     * @test
     */
    public function it_can_give_multiple_action_permissions()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $allow = new AssignsPermissions(new Store('default'), [$user]);
        $allow->to(['edit', 'create']);

        self::assertEquals(2, Permission::ofAuthority($user)->count());
    }

    /**
     * @test
     */
    public function it_can_give_permission_against_model()
    {
        Relation::morphMap([], false);

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $allow = new AssignsPermissions(new Store('default'), [$user]);
        $allow->to(['edit'], TestUserModel::class);

        self::assertEquals(1, Permission::ofAuthority($user)->count());
        $permission = Permission::ofAuthority($user)->first();
        self::assertEquals('edit', $permission->name);
        self::assertEquals(TestUserModel::class, $permission->model_type);
        self::assertNull($permission->model_id);
    }

    /**
     * @test
     */
    public function it_can_give_permission_against_model_with_id()
    {
        Relation::morphMap([], false);

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $allow = new AssignsPermissions(new Store('default'), [$user]);
        $allow->to(['edit'], TestUserModel::class, 10);

        self::assertEquals(1, Permission::ofAuthority($user)->count());
        $permission = Permission::ofAuthority($user)->first();
        self::assertEquals('edit', $permission->name);
        self::assertEquals(TestUserModel::class, $permission->model_type);
        self::assertEquals(10, $permission->model_id);
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
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
    }
}
