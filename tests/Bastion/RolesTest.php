<?php

use Ethereal\Bastion\Store\Store;
use Illuminate\Database\Eloquent\Collection;

class RolesTest extends BaseTestCase
{
    public function test_can_give_and_remove_roles()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));
        $bastion->disableCache();

        $bastion->assign('admin')->to($user);

        static::assertTrue($bastion->is($user)->an('admin'));

        $bastion->assign('user')->to($user);

        static::assertTrue($bastion->is($user)->all('user', 'admin'));
    }

    public function test_can_check_user_role()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->assign('user', 'admin')->to($user);

        static::assertTrue($bastion->is($user)->a('user'));
        static::assertFalse($bastion->is($user)->a('manager'));

        static::assertTrue($bastion->is($user)->an('admin'));
        static::assertFalse($bastion->is($user)->an('elephant'));

        static::assertTrue($bastion->is($user)->notA('manager'));
        static::assertFalse($bastion->is($user)->notA('user'));

        static::assertTrue($bastion->is($user)->notAn('elephant'));
        static::assertFalse($bastion->is($user)->notAn('admin'));

        static::assertTrue($bastion->is($user)->all('user', 'admin'));
        static::assertFalse($bastion->is($user)->all('user', 'manager'));
    }
}
