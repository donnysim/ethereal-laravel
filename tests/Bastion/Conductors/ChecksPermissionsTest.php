<?php

namespace Tests\Bastion\Conductors;

use Ethereal\Bastion\BastionServiceProvider;
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
        $this->app['bastion']->allow($user)->to(['edit']);

        self::assertTrue($user->can('edit'));
        self::assertFalse($user->can('edit', TestUserModel::class));
        self::assertFalse($user->can('edit', new TestUserModel(['id' => 1])));
    }

    /**
     * @test
     */
    public function it_can_check_action_permission_when_action_is_forbidden()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $this->app['bastion']->allow($user)->to(['edit']);
        $this->app['bastion']->forbid($user)->to(['edit']);

        self::assertFalse($user->can('edit', $user));
        self::assertFalse($user->can('create', $user));
    }

    /**
     * @test
     */
    public function it_can_check_action_permission_when_actions_are_forbidden()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $this->app['bastion']->allow($user)->to(['edit']);
        $this->app['bastion']->forbid($user)->to(['*']);

        self::assertFalse($user->can('edit'));
        self::assertFalse($user->can('create'));
    }

    /**
     * @test
     */
    public function it_can_check_action_permission_when_all_actions_are_allowed_and_action_is_forbidden()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $this->app['bastion']->allow($user)->to(['*']);
        $this->app['bastion']->forbid($user)->to(['edit']);

        self::assertFalse($user->can('edit'));
        self::assertTrue($user->can('create'));
    }

    /**
     * @test
     */
    public function it_can_check_class_action_permission()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $this->app['bastion']->allow($user)->to(['edit'], TestUserModel::class);

        self::assertFalse($user->can('edit'));
        self::assertTrue($user->can('edit', TestUserModel::class));
        self::assertTrue($user->can('edit', $user));
    }

    /**
     * @test
     */
    public function it_can_check_class_permission_when_actions_are_forbidden()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $this->app['bastion']->allow($user)->to(['*'], TestUserModel::class);
        $this->app['bastion']->forbid($user)->to(['*']);

        self::assertTrue($user->can('edit', TestUserModel::class));
        self::assertTrue($user->can('edit', $user));
    }

    /**
     * @test
     */
    public function it_can_check_class_permission_when_class_is_forbidden()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $this->app['bastion']->allow($user)->to(['*'], TestUserModel::class);
        $this->app['bastion']->forbid($user)->to(['*'], TestUserModel::class);

        self::assertFalse($user->can('edit', TestUserModel::class));
        self::assertFalse($user->can('edit', $user));
    }

    /**
     * @test
     */
    public function it_can_check_class_permission_when_model_is_forbidden()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $this->app['bastion']->allow($user)->to(['*'], TestUserModel::class);
        $this->app['bastion']->forbid($user)->to(['*'], TestUserModel::class, $user->getKey());

        self::assertTrue($user->can('edit', TestUserModel::class));
        self::assertFalse($user->can('edit', $user));
        self::assertTrue($user->can('edit', new TestUserModel(['id' => 10])));
    }

    /**
     * @test
     */
    public function it_can_check_model_action_permission()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $this->app['bastion']->allow($user)->to(['edit'], TestUserModel::class, 1);

        self::assertFalse($user->can('edit'));
        self::assertFalse($user->can('edit', TestUserModel::class));
        self::assertTrue($user->can('edit', $user));
    }

    /**
     * @test
     */
    public function it_can_check_model_permission_when_class_is_forbidden()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $this->app['bastion']->allow($user)->to(['*'], TestUserModel::class);
        $this->app['bastion']->forbid($user)->to(['*'], TestUserModel::class);

        self::assertFalse($user->can('edit', TestUserModel::class));
        self::assertFalse($user->can('edit', $user));
        self::assertFalse($user->can('edit', new TestUserModel(['id' => 10])));
    }

    /**
     * @test
     */
    public function it_can_check_model_permission_when_model_is_forbidden()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $this->app['bastion']->allow($user)->to(['*'], TestUserModel::class);
        $this->app['bastion']->forbid($user)->to(['*'], TestUserModel::class, $user->getKey());

        self::assertTrue($user->can('edit', TestUserModel::class));
        self::assertFalse($user->can('edit', $user));
        self::assertTrue($user->can('edit', new TestUserModel(['id' => 10])));
    }

    /**
     * @test
     */
    public function it_can_check_permission_when_all_is_forbidden()
    {
        $user = TestUserModel::create(['email' => 'john@example.com']);
        $this->app['bastion']->allow($user)->to(['*'], TestUserModel::class);
        $this->app['bastion']->forbid($user)->to(['*'], '*');

        self::assertFalse($user->can('edit'));
        self::assertFalse($user->can('edit', TestUserModel::class));
        self::assertFalse($user->can('edit', $user));
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
