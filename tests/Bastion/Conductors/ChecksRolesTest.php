<?php

namespace Tests\Bastion\Conductors;

use Ethereal\Bastion\BastionServiceProvider;
use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\ChecksRoles;
use Ethereal\Bastion\Store;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUserModel;

class ChecksRolesTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_check_if_authority_does_not_have_a_role()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $assign = new AssignsRoles(new Store('default'), ['user', 'admin']);
        $assign->to($user);

        $check = new ChecksRoles(new Store('default'), $user);

        self::assertTrue($check->notA('geek'));
        self::assertTrue($check->notAn('elephant'));
    }

    /**
     * @test
     */
    public function it_can_check_if_authority_has_a_role()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $assign = new AssignsRoles(new Store('default'), ['user', 'admin']);
        $assign->to($user);

        $check = new ChecksRoles(new Store('default'), $user);

        self::assertTrue($check->a('user'));
        self::assertTrue($check->an('admin'));
    }

    /**
     * @test
     */
    public function it_can_check_if_authority_has_all_roles()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $assign = new AssignsRoles(new Store('default'), ['user', 'admin']);
        $assign->to($user);

        $check = new ChecksRoles(new Store('default'), $user);

        self::assertTrue($check->notA('geek'));
        self::assertTrue($check->notAn('elephant'));
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
