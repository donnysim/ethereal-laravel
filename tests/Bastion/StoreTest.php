<?php

use Ethereal\Bastion\Store\Store;
use Illuminate\Database\Eloquent\Collection;

class StoreTest extends BaseTestCase
{
    public function test_it_fails_to_get_roles_on_non_existing_model()
    {
        $store = new Store($this->cacheStore());

        static::setExpectedException(InvalidArgumentException::class, 'Authority must exist to retrieve assigned roles.');
        $store->getRoles(new TestUserModel);
    }

    public function test_it_fails_to_get_abilities_on_non_existing_model()
    {
        $store = new Store($this->cacheStore());

        static::setExpectedException(InvalidArgumentException::class, 'Authority must exist to retrieve abilities.');
        $store->getAbilities(new TestUserModel, new Collection());
    }

    public function test_caches_authority_maps()
    {
        $store = new Store($this->cacheStore());

        $user = TestUserModel::random();
        $user->save();

        $store->getMap($user);

        $this->app['db']->listen(function () {
            throw new LogicException('A query for cached map should not be called again.');
        });

        $store->getMap($user);
    }
}