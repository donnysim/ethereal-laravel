<?php

use Ethereal\Bastion\Rucks;
use Ethereal\Bastion\Store;

class RucksTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_can_change_and_resolve_user()
    {
        $rucks = $this->getRucks();
        $rucks->setUserResolver(function () {
            return new TestUserModel;
        });

        self::assertInstanceOf(TestUserModel::class, $rucks->resolveUser());
    }

    /**
     * @test
     */
    public function it_can_create_new_instance_for_user()
    {
        $rucks = $this->getRucks();
        $rucks->setUserResolver(function () {
            return null;
        });
        $userRucks = $rucks->forUser(new TestUserModel);

        self::assertNull($rucks->resolveUser());
        self::assertInstanceOf(TestUserModel::class, $userRucks->resolveUser());
    }

    /**
     * @test
     */
    public function it_can_set_and_get_store()
    {
        $rucks = $this->getRucks();

        self::assertInstanceOf(Store::class, $rucks->getStore());

        $rucks->setStore(null);

        self::assertNull($rucks->getStore());
    }

    /**
     * @test
     */
    public function it_can_register_policies()
    {
        $rucks = $this->getRucks();
        $rucks->policy('model', 'policy');

        self::assertEquals(['model' => 'policy'], $rucks->policies());
    }

    /**
     * @test
     */
    public function it_can_check_if_policy_is_defined()
    {
        $rucks = $this->getRucks();
        $rucks->policy('model', 'policy');

        self::assertTrue($rucks->hasPolicy('model'));
        self::assertFalse($rucks->hasPolicy('policy'));
    }

    /**
     * @test
     */
    public function it_can_register_abilities()
    {
        $rucks = $this->getRucks();
        $rucks->define('kick', function () {

        });

        self::assertTrue($rucks->hasAbility('kick'));
    }

    /**
     * @test
     */
    public function it_can_get_policy_for_class()
    {
        $rucks = $this->getRucks();
        $rucks->policy(TestUserModel::class, TestPolicy::class);

        self::assertInstanceOf(TestPolicy::class, $rucks->getPolicyFor(TestUserModel::class));
    }

    /**
     * @test
     */
    public function it_can_determine_if_policy_is_available()
    {
        $rucks = $this->getRucks();

        self::assertFalse($rucks->hasPolicyCheck('kick', new TestUserModel));

        $rucks->policy(TestUserModel::class, TestPolicy::class);

        self::assertFalse($rucks->hasPolicyCheck('dance', new TestUserModel));
        self::assertTrue($rucks->hasPolicyCheck('kick', new TestUserModel));
    }

    protected function getRucks()
    {
        return new Rucks($this->app, new Store);
    }
}

class TestPolicy
{
    public function kick()
    {

    }
}
