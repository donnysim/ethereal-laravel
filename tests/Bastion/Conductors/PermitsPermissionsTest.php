<?php

namespace Tests\Bastion\Conductors;

use Ethereal\Bastion\Conductors\AssignsPermissions;
use Ethereal\Bastion\Conductors\ForbidsPermissions;
use Ethereal\Bastion\Conductors\PermitsPermissions;
use Ethereal\Bastion\Database\AssignedPermission;
use Ethereal\Bastion\Store;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUserModel;

class PermitsPermissionsTest extends TestCase
{
    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     */
    public function it_creates_forbid_entry_even_if_allow_permission_is_present()
    {
        $authority = TestUserModel::create(['email' => 'john@doe.com']);
        $forbid = new ForbidsPermissions(new Store(), [$authority]);
        $forbid->to('action');
        $permit = new PermitsPermissions(new Store(), [$authority]);
        $permit->to(['action']);

        self::assertFalse(AssignedPermission::where([
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
