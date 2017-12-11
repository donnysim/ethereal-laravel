<?php

namespace Tests\Bastion\Conductors;

use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\ChecksRoles;
use Ethereal\Bastion\Database\Role;
use Ethereal\Bastion\Store;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUserModel;

class ChecksRolesTest extends TestCase
{
    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidRoleException
     */
    public function it_can_check_if_authority_does_not_have_a_role()
    {
        $role1 = Role::create(['name' => 'dnhr1']);
        $role2 = Role::create(['name' => 'dnhr2']);

        $user = TestUserModel::create(['email' => 'john@doe.com']);
        $assign = new AssignsRoles(new Store(), [$role1, $role2]);
        $assign->to($user);

        $check = new ChecksRoles(new Store(), $user);

        self::assertTrue($check->notA('dnhr3'));
        self::assertTrue($check->notAn('dnhr4'));
    }

    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidRoleException
     */
    public function it_can_check_if_authority_has_a_role()
    {
        $role1 = Role::create(['name' => 'ahr1']);
        $role2 = Role::create(['name' => 'ahr2']);

        $user = TestUserModel::create(['email' => 'john@doe.com']);
        $assign = new AssignsRoles(new Store(), [$role1, $role2]);
        $assign->to($user);

        $check = new ChecksRoles(new Store(), $user);

        self::assertTrue($check->a('ahr1'));
        self::assertTrue($check->an('ahr2'));
    }

    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidRoleException
     */
    public function it_can_check_if_authority_has_all_roles()
    {
        $role1 = Role::create(['name' => 'ahar1']);
        $role2 = Role::create(['name' => 'ahar2']);

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $assign = new AssignsRoles(new Store(), [$role1, $role2]);
        $assign->to($user);

        $check = new ChecksRoles(new Store(), $user);

        self::assertTrue($check->all('ahar1', 'ahar2'));
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
