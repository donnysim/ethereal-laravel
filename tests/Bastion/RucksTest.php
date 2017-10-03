<?php

namespace Tests\Bastion\Database;

use Ethereal\Bastion\Rucks;
use Ethereal\Bastion\Store;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUserModel;

class RucksTest extends TestCase
{
    /**
     * @test
     */
    public function it_fails_when_user_is_not_authenticated()
    {
        $rucks = $this->getRucks();
        $rucks->setUserResolver(function () {
            return null;
        });
        self::assertFalse($rucks->check('anything')->allowed());
    }

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
    public function check_fails_if_no_policy_is_available()
    {
        $rucks = $this->getRucks();
        $rucks->setUserResolver(function () {
            return new TestUserModel;
        });

        self::assertTrue($rucks->check('kick', TestUserModel::class)->denied());
    }

    /**
     * @test
     */
    public function check_succeeds_if_before_callback_returns_true()
    {
        $rucks = $this->getRucks();
        $rucks->setUserResolver(function () {
            return new TestUserModel;
        });
        $rucks->before(function () {
            return true;
        });

        self::assertTrue($rucks->check('kick', TestUserModel::class)->allowed());
    }

    /**
     * @test
     */
    public function check_fails_if_before_callback_returns_false()
    {
        $rucks = $this->getRucks();
        $rucks->setUserResolver(function () {
            return new TestUserModel;
        });
        $rucks->before(function () {
            return false;
        });

        self::assertTrue($rucks->check('kick', TestUserModel::class)->denied());
    }

    /**
     * @test
     */
    public function it_checks_policy_if_before_callbacks_returns_null()
    {
        $rucks = $this->getRucks();
        $rucks->policy(TestUserModel::class, TestPolicy::class);
        $rucks->setUserResolver(function () {
            return new TestUserModel;
        });
        $rucks->before(function () {
            return null;
        });

        self::assertTrue($rucks->check('allow', TestUserModel::class)->allowed());
        self::assertTrue($rucks->check('deny', TestUserModel::class)->denied());
    }

    /**
     * @test
     */
    public function it_fails_if_no_policies_could_determine_access()
    {
        $rucks = $this->getRucks();
        $rucks->policy(TestUserModel::class, TestPolicy::class);
        $rucks->setUserResolver(function () {
            return new TestUserModel;
        });
        $rucks->before(function () {
            return null;
        });

        self::assertTrue($rucks->check('kick', TestUserModel::class)->denied());
    }

    protected function getRucks()
    {
        return new Rucks($this->app, new Store('default'));
    }
}

class TestPolicy
{
    public function kick()
    {

    }

    public function allow()
    {
        return true;
    }

    public function deny()
    {
        return false;
    }
}
