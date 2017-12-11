<?php

namespace Tests\Bastion\Conductors;

use Ethereal\Bastion\Conductors\AssignsPermissions;
use Ethereal\Bastion\Conductors\ForbidsPermissions;
use Ethereal\Bastion\Database\AssignedPermission;
use Ethereal\Bastion\Store;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUserModel;

class ForbidsPermissionsTest extends TestCase
{
    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     */
    public function it_creates_forbid_entry_even_if_allow_permission_is_present()
    {
        $authority = TestUserModel::create(['email' => 'john@doe.com']);
        $allow = new AssignsPermissions(new Store(), [$authority]);
        $forbid = new ForbidsPermissions(new Store(), [$authority]);

        $allow->to('action');

        self::assertTrue(AssignedPermission::where([
            'model_id' => $authority->getKey(),
            'model_type' => $authority->getMorphClass(),
            'forbid' => 0,
        ])->exists());

        $forbid->to('action');

        self::assertFalse(AssignedPermission::where([
            'model_id' => $authority->getKey(),
            'model_type' => $authority->getMorphClass(),
            'forbid' => 0,
        ])->exists());
        self::assertTrue(AssignedPermission::where([
            'model_id' => $authority->getKey(),
            'model_type' => $authority->getMorphClass(),
            'forbid' => 1,
        ])->exists());
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
