<?php

namespace Tests\Bastion\Conductors;

use Ethereal\Bastion\BastionServiceProvider;
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
     */
    public function it_can_remove_roles_from_model_class_and_ids()
    {
        $user = TestUserModel::create(['email' => 'test']);

        $assign = new AssignsRoles(new Store('default'), ['user', 'admin']);
        $assign->to(TestUserModel::class, [$user->getKey(), $user->getKey() + 1]);

        self::assertEquals(2, Role::ofAuthority($user)->count());

        $remove = new RemovesRoles(new Store('default'), ['user']);
        $remove->from(TestUserModel::class, [$user->getKey()]);

        self::assertEquals(1, Role::ofAuthority($user)->count());
    }

    /**
     * @test
     */
    public function it_can_remove_roles_from_user()
    {
        $user = TestUserModel::create(['email' => 'test']);

        $assign = new AssignsRoles(new Store('default'), ['user', 'admin']);
        $assign->to(TestUserModel::class, [$user->getKey(), $user->getKey() + 1]);

        self::assertEquals(2, Role::ofAuthority($user)->count());

        $remove = new RemovesRoles(new Store('default'), ['user']);
        $remove->from($user);

        self::assertEquals(1, Role::ofAuthority($user)->count());
    }

    /**
     * @test
     */
    public function it_does_not_clear_all_roles_when_model_does_not_exist()
    {
        $assign = new AssignsRoles(new Store('default'), ['user', 'admin']);
        $assign->to(TestUserModel::class, [1, 2, 3]);

        self::assertEquals(2, Role::count());

        $remove = new RemovesRoles(new Store('default'), ['user']);
        $remove->from(new TestUserModel());

        self::assertEquals(2, Role::count());
    }

    /**
     * @test
     */
    public function it_only_removes_roles_from_current_guard()
    {
        $user = TestUserModel::create(['email' => 'test']);
        $assign = new AssignsRoles(new Store('default'), ['user', 'admin']);
        $assign->to($user);

        $assign = new AssignsRoles(new Store('other'), ['user', 'admin']);
        $assign->to($user);

        $remove = new RemovesRoles(new Store('default'), ['user']);
        $remove->from($user);

        self::assertEquals(1, (new Store('default'))->getMap($user)->getRoles()->count());
        self::assertEquals(2, (new Store('other'))->getMap($user)->getRoles()->count());
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
