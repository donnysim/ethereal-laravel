<?php

use Ethereal\Bastion\Rucks;

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

    protected function getRucks()
    {
        return new Rucks($this->app);
    }
}
