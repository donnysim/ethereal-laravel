<?php

namespace Tests\Bastion\Conductors;

use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Database\AssignedRole;
use Ethereal\Bastion\Database\Role;
use Ethereal\Bastion\Helper;
use Ethereal\Bastion\Store;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUserModel;

class AssignsRolesTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_assign_roles_to_model_class_and_ids()
    {
        $assign = new AssignsRoles(new Store('default'), ['user', 'admin']);

        $assign->to(TestUserModel::class, [1, 2, 3]);

        $user = new TestUserModel(['id' => 1]);
        $user->exists = true;

        self::assertEquals(2, Role::ofAuthority($user)->count());
        self::assertEquals(2, Role::ofAuthority($user->setAttribute('id', 2))->count());
        self::assertEquals(2, Role::ofAuthority($user->setAttribute('id', 3))->count());
        self::assertEquals(6, AssignedRole::whereIn('model_id', [1, 2, 3])
            ->where('model_type', Helper::getMorphOfClass(TestUserModel::class))
            ->count()
        );
    }

    /**
     * @test
     */
    public function it_can_assign_roles_to_user()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $assign = new AssignsRoles(new Store('default'), ['user', 'admin']);

        $assign->to($user);

        self::assertEquals(2, Role::ofAuthority($user)->count());
        self::assertEquals(2, AssignedRole::where([
            'model_id' => $user->getKey(),
            'model_type' => $user->getMorphClass(),
        ])->count());
    }

    /**
     * @test
     */
    public function it_does_not_add_existing_roles()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $assign = new AssignsRoles(new Store('default'), ['user', 'admin']);

        $assign->to($user);

        self::assertEquals(2, Role::ofAuthority($user)->count());
        self::assertEquals(2, AssignedRole::where([
            'model_id' => $user->getKey(),
            'model_type' => $user->getMorphClass(),
        ])->count());

        $assign = new AssignsRoles(new Store('default'), ['user', 'admin', 'tester']);
        $assign->to($user);

        self::assertEquals(3, Role::ofAuthority($user)->count());
        self::assertEquals(3, AssignedRole::where([
            'model_id' => $user->getKey(),
            'model_type' => $user->getMorphClass(),
        ])->count());
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
