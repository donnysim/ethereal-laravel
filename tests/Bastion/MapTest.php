<?php

use Ethereal\Bastion\Bastion;
use Ethereal\Bastion\Store;

class MapTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     */
    public function it_can_get_role_names()
    {
        $this->migrate();

        $store = new Store;
        $bastion = new Bastion($this->app, $store);
        $user = TestUserModel::create(['email' => 'john@example.com']);

        $bastion->assign('admin')->to($user);

        $map = $store->getMap($user);

        self::assertEquals(['admin'], $map->getRoleNames()->all());
    }

    /**
     * @test
     */
    public function it_can_get_allowed_abilities()
    {
        $this->migrate();

        $store = new Store;
        $bastion = new Bastion($this->app, $store);
        $user = TestUserModel::create(['email' => 'john@example.com']);

        $bastion->assign('admin')->to($user);
        $bastion->allow('admin')->to('kick');
        $bastion->forbid('admin')->to('punch');

        $map = $store->getMap($user);

        self::assertEquals(['kick'], $map->getAllowedAbilities()->keys()->all());
    }

    /**
     * @test
     */
    public function it_can_get_forbidden_abilities()
    {
        $this->migrate();

        $store = new Store;
        $bastion = new Bastion($this->app, $store);
        $user = TestUserModel::create(['email' => 'john@example.com']);

        $bastion->assign('admin')->to($user);
        $bastion->allow('admin')->to('kick');
        $bastion->forbid('admin')->to('punch');

        $map = $store->getMap($user);

        self::assertEquals(['punch'], $map->getForbiddenAbilities()->keys()->all());
    }

    /**
     * @test
     */
    public function it_can_check_if_ability_identifier_is_forbidden()
    {
        $this->migrate();

        $store = new Store;
        $bastion = new Bastion($this->app, $store);
        $user = TestUserModel::create(['email' => 'john@example.com']);

        $bastion->assign('admin')->to($user);
        $bastion->allow('admin')->to('kick');
        $bastion->forbid('admin')->to('punch');

        $map = $store->getMap($user);

        self::assertFalse($map->isForbidden('kick-*-*'));
        self::assertTrue($map->isForbidden('punch-*-*'));
    }
}
