<?php

namespace Tests\Bastion\Conductors;

use Ethereal\Bastion\BastionServiceProvider;
use Ethereal\Bastion\Conductors\AssignsPermissions;
use Ethereal\Bastion\Store;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUserModel;

class ChecksPermissionsTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_check_action_permission()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $allow = new AssignsPermissions(new Store('default'), [$user]);
        $allow->to(['edit']);

        self::assertTrue($user->can('edit'));
        self::assertFalse($user->can('edit', TestUserModel::class));
        self::assertFalse($user->can('edit', new TestUserModel(['id' => 1])));
    }

    /**
     * @test
     */
    public function it_can_check_class_action_permission()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $allow = new AssignsPermissions(new Store('default'), [$user]);
        $allow->to(['edit'], TestUserModel::class);

        self::assertFalse($user->can('edit'));
        self::assertTrue($user->can('edit', TestUserModel::class));
        self::assertTrue($user->can('edit', $user));
    }

    /**
     * @test
     */
    public function it_can_check_model_action_permission()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $allow = new AssignsPermissions(new Store('default'), [$user]);
        $allow->to(['edit'], TestUserModel::class, 1);

        self::assertFalse($user->can('edit'));
        self::assertFalse($user->can('edit', TestUserModel::class));
        self::assertTrue($user->can('edit', $user));
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

        $this->app['bastion']->disableCache();
    }
}
