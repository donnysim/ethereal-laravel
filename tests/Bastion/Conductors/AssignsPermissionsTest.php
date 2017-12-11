<?php

namespace Tests\Bastion\Conductors;

use Ethereal\Bastion\Conductors\AssignsPermissions;
use Ethereal\Bastion\Database\Permission;
use Ethereal\Bastion\Store;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUserModel;

class AssignsPermissionsTest extends TestCase
{
    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     */
    public function it_can_give_action_permission()
    {
        $authority = TestUserModel::create(['email' => 'john@doe.com']);
        $allow = new AssignsPermissions(new Store(), [$authority]);

        self::assertEquals(0, Permission::ofAuthority($authority)->count());
        $allow->to(['edit']);
        self::assertEquals('edit', Permission::ofAuthority($authority)->first()->identifier);
    }

    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     */
    public function it_can_give_action_permission_on_model()
    {
        $authority = TestUserModel::create(['email' => 'john@doe.com']);
        $allow = new AssignsPermissions(new Store(), [$authority]);

        self::assertEquals(0, Permission::ofAuthority($authority)->count());
        $allow->to(['edit'], TestUserModel::class);
        self::assertEquals('edit-Tests\Models\TestUserModel', Permission::ofAuthority($authority)->first()->identifier);
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

        self::assertEquals(0, Permission::ofAuthority($authority)->count());
        $allow->to(['edit'], TestUserModel::class, 1);
        self::assertEquals('edit-Tests\Models\TestUserModel-1', Permission::ofAuthority($authority)->first()->identifier);
    }

    /**
     * @test
     * @expectedException \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public function it_throws_exception_if_authority_does_not_exist()
    {
        $allow = new AssignsPermissions(new Store(), [new TestUserModel()]);
        $allow->to(['edit']);
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
