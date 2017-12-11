<?php

namespace Tests\Bastion\Conductors;

use Ethereal\Bastion\Conductors\AssignsPermissions;
use Ethereal\Bastion\Conductors\RemovesPermissions;
use Ethereal\Bastion\Database\Permission;
use Ethereal\Bastion\Store;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUserModel;

class RemovesPermissionsTest extends TestCase
{
    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     */
    public function it_can_remove_action_permission()
    {
        $authority = TestUserModel::create(['email' => 'john@doe.com']);
        $allow = new AssignsPermissions(new Store(), [$authority]);
        $allow->to(['edit']);
        $remove = new RemovesPermissions(new Store(), [$authority]);
        $remove->to(['edit']);
        self::assertEquals(0, Permission::ofAuthority($authority)->count());
    }

    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     */
    public function it_can_remove_action_permission_on_model()
    {
        $authority = TestUserModel::create(['email' => 'john@doe.com']);
        $allow = new AssignsPermissions(new Store(), [$authority]);
        $allow->to(['edit'], TestUserModel::class);
        $remove = new RemovesPermissions(new Store(), [$authority]);
        $remove->to(['edit'], TestUserModel::class);

        self::assertEquals(0, Permission::ofAuthority($authority)->count());
    }

    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     */
    public function it_can_give_action_permission_on_specific_model()
    {
        $authority = TestUserModel::create(['email' => 'john@doe.com']);
        $allow = new AssignsPermissions(new Store(), [$authority]);
        $allow->to(['edit'], TestUserModel::class, 1);
        $remove = new RemovesPermissions(new Store(), [$authority]);
        $remove->to(['edit'], TestUserModel::class, 1);

        self::assertEquals(0, Permission::ofAuthority($authority)->count());
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
