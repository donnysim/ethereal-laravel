<?php

namespace Tests\Bastion\Database;

use Ethereal\Bastion\Database\AssignedRole;
use Ethereal\Bastion\Database\Role;
use Ethereal\Database\Ethereal;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Orchestra\Testbench\Traits\WithLoadMigrationsFrom;

class RoleTest extends TestCase
{
    use WithLoadMigrationsFrom;

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function authority_must_exist_to_get_permissions()
    {
        Role::ofAuthority(new Ethereal());
    }

    /**
     * @test
     */
    public function it_gets_all_roles_of_authority()
    {
        $role = Role::create([
            'name' => 'test',
            'guard' => 'default',
            'system' => true,
            'private' => true,
        ]);

        $role2 = Role::create([
            'name' => 'test-2',
            'guard' => 'next',
            'system' => true,
            'private' => true,
        ]);

        AssignedRole::create(['role_id' => $role->getKey(), 'model_id' => 1, 'model_type' => Ethereal::class]);
        AssignedRole::create(['role_id' => $role2->getKey(), 'model_id' => 1, 'model_type' => Ethereal::class]);

        $authority = new Ethereal(['id' => 1]);
        $authority->exists = true;
        self::assertEquals(2, Role::ofAuthority($authority)->count());

        $authority = new Ethereal(['id' => 1]);
        $authority->exists = true;
        self::assertEquals(1, Role::ofAuthority($authority, 'default')->count());

        $authority = new Ethereal(['id' => 2]);
        $authority->exists = true;
        self::assertEquals(0, Role::ofAuthority($authority)->count());
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
    }
}
