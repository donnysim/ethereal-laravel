<?php

use Ethereal\Bastion\Bastion;
use Ethereal\Bastion\Rucks;

class BastionTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_can_get_rucks_instance_for_type()
    {
        $bastion = $this->getBastion();

        $userRucks = $bastion->rucks();
        $userRucks->type = 'user';

        $adminRucks = $bastion->rucks('admin');
        $adminRucks->type = 'admin';

        self::assertInstanceOf(Rucks::class, $userRucks);
        self::assertEquals('user', $userRucks->type);

        self::assertInstanceOf(Rucks::class, $adminRucks);
        self::assertEquals('admin', $adminRucks->type);

        self::assertEquals('user', $bastion->rucks()->type);
        self::assertEquals('admin', $bastion->rucks('admin')->type);
    }

    /**
     * @test
     */
    public function it_can_change_default_rucks_type()
    {
        $bastion = $this->getBastion();

        $userRucks = $bastion->rucks();
        $userRucks->type = 'user';

        $bastion->useType('admin');
        self::assertFalse(property_exists($bastion->rucks(), 'type'));
    }

    /**
     * @return \Ethereal\Bastion\Bastion
     */
    protected function getBastion()
    {
        return new Bastion($this->app);
    }
}
