<?php

use Ethereal\Bastion\Bastion;
use Ethereal\Bastion\Map;
use Ethereal\Bastion\Store;

class StoreTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     */
    public function it_maps_authority_roles_and_abilities()
    {
        $this->migrate();

        $store = new Store;
        $bastion = new Bastion($this->app, $store);
        $user = TestUserModel::create(['email' => 'john@example.com']);

        $bastion->assign('admin')->to($user);
        $bastion->allow('admin')->to('kick');
        $bastion->allow($user)->to('punch');

        $map = $store->getMap($user);

        self::assertInstanceOf(Map::class, $map);
        self::assertEquals(1, $map->getRoles()->count());
        self::assertEquals(2, $map->getAbilities()->count());
    }

    /**
     * @test
     */
    public function it_can_check_role()
    {
        $this->migrate();

        $store = new Store;
        $bastion = new Bastion($this->app, $store);
        $user = TestUserModel::create(['email' => 'john@example.com']);

        $bastion->assign('admin')->to($user);

        self::assertTrue($store->hasRole($user, 'admin'));
        self::assertTrue($store->hasRole($user, ['user'], 'not'));
        self::assertFalse($store->hasRole($user, ['user', 'admin'], 'not'));
        self::assertFalse($store->hasRole($user, ['user', 'admin'], 'and'));
    }

    /**
     * @test
     */
    public function it_can_check_if_ability_is_allowed()
    {
        $this->migrate();

        $store = new Store;
        $bastion = new Bastion($this->app, $store);
        $user = TestUserModel::create(['email' => 'john@example.com']);

        $bastion->assign('admin')->to($user);
        $bastion->allow('admin')->to(['kick', 'punch']);
        $bastion->forbid('admin')->to('punch');

        self::assertTrue($store->hasAbility($user, 'kick'));
    }

    /**
     * @test
     */
    public function it_can_check_if_ability_is_allowed_including_model()
    {
        $this->migrate();

        $store = new Store;
        $bastion = new Bastion($this->app, $store);
        $user = TestUserModel::create(['email' => 'john@example.com']);

        $bastion->assign('admin')->to($user);
        $bastion->allow('admin')->to('kick', $user);

        self::assertFalse($store->hasAbility($user, 'kick'));
        self::assertTrue($store->hasAbility($user, 'kick', $user));
    }

    /**
     * @test
     */
    public function it_can_check_if_ability_is_allowed_including_group()
    {
        $this->migrate();

        $store = new Store;
        $bastion = new Bastion($this->app, $store);
        $user = TestUserModel::create(['email' => 'john@example.com']);

        $bastion->assign('admin')->to($user);
        $bastion->allow('admin')->group('employee')->to('kick', $user);

        self::assertFalse($store->hasAbility($user, 'kick', $user));
        self::assertTrue($store->hasAbility($user, 'kick', $user, 'employee'));
    }

    /**
     * @test
     */
    public function it_can_check_if_ability_is_allowed_including_parent()
    {
        $this->migrate();

        $store = new Store;
        $bastion = new Bastion($this->app, $store);
        $user = TestUserModel::create(['email' => 'john@example.com']);

        $bastion->assign('admin')->to($user);
        $bastion->allow('admin')->group('employee')->parent($user)->to('kick', $user);

        self::assertFalse($store->hasAbility($user, 'kick', $user, 'employee'));
        self::assertTrue($store->hasAbility($user, 'kick', $user, 'employee', $user));
    }
}
