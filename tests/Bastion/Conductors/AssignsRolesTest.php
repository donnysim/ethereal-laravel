<?php

namespace Tests\Bastion\Conductors;

use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Database\AssignedRole;
use Ethereal\Bastion\Database\Role;
use Ethereal\Bastion\Store;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUserModel;

class AssignsRolesTest extends TestCase
{
    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidRoleException
     */
    public function it_assigns_roles_to_model()
    {
        $role1 = Role::create(['name' => 'am1']);
        $role2 = Role::create(['name' => 'am2']);
        Role::create(['name' => 'am3']);

        $authority = TestUserModel::create(['email' => 'john@doe.com']);
        $assign = new AssignsRoles(new Store(), [$role1, $role2]);

        $assign->to($authority);
        self::assertEquals([$role1->getKey(), $role2->getKey()], Role::allRoles($authority)->pluck('id')->all());
    }

    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidRoleException
     */
    public function it_can_assign_roles_to_model_class_and_ids()
    {
        $authority1 = TestUserModel::create(['email' => 'john@doe1.com']);
        $authority2 = TestUserModel::create(['email' => 'john@doe2.com']);
        $authority3 = TestUserModel::create(['email' => 'john@doe3.com']);

        $role1 = Role::create(['name' => 'ami1']);
        $role2 = Role::create(['name' => 'ami2']);
        $assign = new AssignsRoles(new Store(), [$role1, $role2]);

        $assign->to(TestUserModel::class, [$authority1->getKey(), $authority2->getKey(), $authority3->getKey()]);

        $authority = new TestUserModel(['id' => 1]);
        $authority->exists = true;

        self::assertEquals(2, Role::allRoles($authority)->count());
        self::assertEquals(2, Role::allRoles($authority->setAttribute('id', 2))->count());
        self::assertEquals(2, Role::allRoles($authority->setAttribute('id', 3))->count());
        self::assertEquals(6, AssignedRole::whereIn('model_id', [1, 2, 3])
            ->where('model_type', $authority->getMorphClass())
            ->count()
        );
    }

    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidRoleException
     */
    public function it_does_not_add_existing_roles()
    {
        $role1 = Role::create(['name' => 'ier1']);
        $role2 = Role::create(['name' => 'ier2']);

        $authority = TestUserModel::create(['email' => 'john@example.com']);
        $assign = new AssignsRoles(new Store(), [$role1, $role2]);
        $assign->to($authority);

        self::assertEquals(2, Role::allRoles($authority)->count());
        self::assertEquals(2, AssignedRole::where([
            'model_id' => $authority->getKey(),
            'model_type' => $authority->getMorphClass(),
        ])->count());

        $role3 = Role::create(['name' => 'ier3']);
        $assign = new AssignsRoles(new Store(), [$role1, $role2, $role3]);
        $assign->to($authority);

        self::assertEquals(3, Role::allRoles($authority)->count());
        self::assertEquals(3, AssignedRole::where([
            'model_id' => $authority->getKey(),
            'model_type' => $authority->getMorphClass(),
        ])->count());
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
