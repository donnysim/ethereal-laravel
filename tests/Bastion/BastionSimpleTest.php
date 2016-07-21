<?php

class BastionSimpleTest extends BaseTestCase
{
    public function test_bastion_can_give_and_remove_abilities()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('edit-profile');
        static::assertTrue($bastion->allows('edit-profile'));

        $bastion->disallow($user)->to('edit-profile');
        static::assertTrue($bastion->denies('edit-profile'));

        $bastion->allow($user)->to(['edit-profile', 'edit-picture']);
        static::assertTrue($bastion->allows('edit-profile'));
        static::assertTrue($bastion->allows('edit-picture'));

        $bastion->disallow($user)->to(['edit-profile', 'edit-picture']);
        static::assertTrue($bastion->denies('edit-profile'));
        static::assertTrue($bastion->denies('edit-picture'));
    }

    public function test_bastion_can_give_and_remove_wildcard_abilities()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*');

        $this->assertTrue($bastion->allows('edit-site'));
        $this->assertTrue($bastion->allows('ban-users'));
        $this->assertTrue($bastion->allows('*'));

        $bastion->disallow($user)->to('*');

        $this->assertTrue($bastion->denies('edit-site'));
    }

    public function test_bastion_can_deny_access_if_set_to_work_exclusively()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->getGate()->define('access-dashboard', function () {
            return true;
        });

        $this->assertTrue($bastion->allows('access-dashboard'));

        $bastion->exclusive();

        $this->assertTrue($bastion->denies('access-dashboard'));
    }

    public function test_bastion_can_ignore_duplicate_ability_allowances()
    {
        $user1 = User::create(['email' => 'test@email.com', 'password' => 'empty']);
        $user2 = User::create(['email' => 'test2@email.com', 'password' => 'empty']);

        $bastion = $this->bastion($user1);

        $bastion->allow($user1)->to('ban-users');
        $bastion->allow($user1)->to('ban-users');

        $bastion->allow($user1)->to('ban', $user2);
        $bastion->allow($user1)->to('ban', $user2);

        $bastion->allow('admin')->to('ban-users');
        $bastion->allow('admin')->to('ban-users');

        $bastion->allow('admin')->to('ban', $user1);
        $bastion->allow('admin')->to('ban', $user1);
    }

    public function test_bastion_can_give_and_remove_roles()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow('admin')->to('edit-site');
        $bastion->assign('admin')->to($user);

        $editor = \Ethereal\Bastion\Helper::rolesModel()->create(['name' => 'editor']);
        $bastion->allow($editor)->to('edit-site');
        $bastion->assign($editor)->to($user);

        $this->assertTrue($bastion->allows('edit-site'));

        $bastion->retract('admin')->from($user);
        $bastion->retract($editor)->from($user);

        $this->assertTrue($bastion->denies('edit-site'));
    }

    public function test_bastion_can_ignore_duplicate_role_assignments()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->assign('admin')->to($user);
        $bastion->assign('admin')->to($user);
    }

    public function test_bastion_can_disallow_abilities_on_roles()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow('admin')->to('edit-site');
        $bastion->disallow('admin')->to('edit-site');
        $bastion->assign('admin')->to($user);

        $this->assertTrue($bastion->denies('edit-site'));
    }

    public function test_bastion_can_check_user_roles()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $this->assertTrue($bastion->is($user)->notA('moderator'));
        $this->assertTrue($bastion->is($user)->notAn('editor'));
        $this->assertFalse($bastion->is($user)->an('admin'));

        $bastion = $this->bastion($user = User::create(['email' => 'test2@email.com', 'password' => 'empty']));

        $bastion->assign('moderator')->to($user);
        $bastion->assign('editor')->to($user);

        $this->assertTrue($bastion->is($user)->a('moderator'));
        $this->assertTrue($bastion->is($user)->an('editor'));
        $this->assertFalse($bastion->is($user)->notAn('editor'));
        $this->assertFalse($bastion->is($user)->an('admin'));
    }

    public function test_bastion_can_check_multiple_user_roles()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $this->assertTrue($bastion->is($user)->notAn('editor', 'moderator'));
        $this->assertTrue($bastion->is($user)->notAn('admin', 'moderator'));

        $bastion = $this->bastion($user = User::create(['email' => 'test2@email.com', 'password' => 'empty']));
        $bastion->assign('moderator')->to($user);
        $bastion->assign('editor')->to($user);

        $this->assertTrue($bastion->is($user)->a('subscriber', 'moderator'));
        $this->assertTrue($bastion->is($user)->an('admin', 'editor'));
        $this->assertTrue($bastion->is($user)->all('editor', 'moderator'));
        $this->assertFalse($bastion->is($user)->notAn('editor', 'moderator'));
        $this->assertFalse($bastion->is($user)->all('admin', 'moderator'));
    }
}
