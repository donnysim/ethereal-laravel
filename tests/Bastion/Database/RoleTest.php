<?php

namespace Tests\Bastion\Database;

use Ethereal\Bastion\Database\AssignedRole;
use Ethereal\Bastion\Database\Role;
use Ethereal\Database\Ethereal;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUserModel;

class RoleTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_assigned_to_authority()
    {
        $authority = TestUserModel::create(['email' => 'john@doe.com']);
        $role = Role::create(['name' => 'all-guard-role1', 'scope' => 'all-guard1']);
        $role->assignTo($authority);

        self::assertEquals(1, AssignedRole::where([
            'model_id' => $authority->getKey(),
            'model_type' => $authority->getMorphClass(),
        ])->count());
    }

    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidRoleException
     */
    public function it_returns_keyed_roles_list_by_id()
    {
        $role = Role::create(['name' => 'keyed']);
        self::assertEquals([$role->getKey()], Role::ensureRoles(['keyed'])->keys()->all());
    }

    /**
     * @test
     * @expectedException \Ethereal\Bastion\Exceptions\InvalidRoleException
     * @throws \Ethereal\Bastion\Exceptions\InvalidRoleException
     */
    public function it_throws_error_ensuring_model_that_does_not_exist()
    {
        $role = new Role(['name' => 'model']);
        Role::ensureRoles([$role]);
    }

    /**
     * @test
     * @expectedException \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public function it_throws_error_if_authority_does_not_exist()
    {
        Role::allRoles(new Ethereal());
    }

    /**
     * @test
     * @expectedException \Ethereal\Bastion\Exceptions\InvalidRoleException
     * @throws \Ethereal\Bastion\Exceptions\InvalidRoleException
     */
    public function it_throws_error_if_ensuring_non_existing_role()
    {
        Role::create(['name' => 'exists']);
        Role::ensureRoles(['exists', 'missing']);
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
