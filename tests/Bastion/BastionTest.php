<?php

use Ethereal\Bastion\Bastion;
use Ethereal\Bastion\Rucks;
use Ethereal\Bastion\Store;

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
     * @test
     */
    public function it_passes_methods_directly_to_rucks()
    {
        $bastion = $this->getBastion();

        $bastion->policy('model', 'policy');
        self::assertEquals(['model' => 'policy'], $bastion->policies());
    }

    /**
     * @test
     */
    public function it_can_set_and_get_store()
    {
        $rucks = $this->getBastion();

        self::assertInstanceOf(Store::class, $rucks->getStore());

        $rucks->setStore(null);

        self::assertNull($rucks->getStore());
    }

    public function general_usage_ideas()
    {
        // TODO remove

        // 1. Have bastion
        $bastion = $this->getBastion();

        // 2. Register policies
        $bastion->policy('random', 'policy');
        $bastion->rucks('employee')->policy('random', 'policy');

        // 3. Assignment, on is permission group
        $bastion->allow(new TestUserModel)->of('employee class or model', 'id or ids')->group('employee')->to('dance');

        $bastion->assign('admin')->to(new TestUserModel);
        $bastion->retract('admin')->from('user model[s] or class', 'id or ids');
    }

    /**
     * @return \Ethereal\Bastion\Bastion
     */
    protected function getBastion()
    {
        return new Bastion($this->app, new Store);
    }
}
