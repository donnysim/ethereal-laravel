<?php

use Ethereal\Bastion\Store\Store;
use Illuminate\Database\Eloquent\Collection;

class RolesTest extends BaseTestCase
{
    public function test_bastion_can_give_and_remove_roles()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));
        $bastion->disableCache();

        $bastion->assign('admin')->to($user);

        static::assertTrue($bastion->is($user)->an('admin'));

        $bastion->assign('user')->to($user);

        static::assertTrue($bastion->is($user)->all('user', 'admin'));
    }
}
