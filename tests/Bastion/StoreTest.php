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
}