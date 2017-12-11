<?php

namespace Tests\Bastion\Conductors;

use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\RemovesRoles;
use Ethereal\Bastion\Database\Role;
use Ethereal\Bastion\Store;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUserModel;

class RemovesRolesTest extends TestCase
{
    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidRoleException
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public function it_can_remove_roles_from_model_class_and_ids()
    {
        $authority = TestUserModel::create(['email' => 'test']);
        $role1 = Role::create(['name' => 'rrci1']);
        $role2 = Role::create(['name' => 'rrci2']);

        $assign = new AssignsRoles(new Store(), [$role1, $role2]);
        $assign->to(TestUserModel::class, [$authority->getKey()]);

        self::assertEquals(2, Role::allRoles($authority)->count());

        $remove = new RemovesRoles(new Store(), [$role1]);
        $remove->from(TestUserModel::class, [$authority->getKey()]);

        self::assertEquals(1, Role::allRoles($authority)->count());
    }

    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidRoleException
     */
    public function it_removes_roles_from_model()
    {
        $authority = TestUserModel::create(['email' => 'test']);
        $role1 = Role::create(['name' => 'rmm1']);
        $role2 = Role::create(['name' => 'rmm2']);
        $role1->assignTo($authority);
        $role2->assignTo($authority);

        self::assertEquals(2, Role::allRoles($authority)->count());

        $remove = new RemovesRoles(new Store(), [$role1]);
        $remove->from($authority);

        self::assertEquals(1, Role::allRoles($authority)->count());
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
