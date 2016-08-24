<?php

class BastionSimpleTest extends BaseTestCase
{
    public function test_bastion_can_give_and_remove_abilities()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

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
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*');

        static::assertTrue($bastion->allows('edit-site'));
        static::assertTrue($bastion->allows('ban-users'));
        static::assertTrue($bastion->allows('*'));

        $bastion->disallow($user)->to('*');

        static::assertTrue($bastion->denies('edit-site'));
    }

    public function test_bastion_can_deny_access_if_set_to_work_exclusively()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->getGate()->define('access-dashboard', function () {
            return true;
        });

        static::assertTrue($bastion->allows('access-dashboard'));

        $bastion->exclusive();

        static::assertTrue($bastion->denies('access-dashboard'));
    }

    public function test_bastion_can_ignore_duplicate_ability_allowances()
    {
        $user1 = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']);
        $user2 = TestUserModel::create(['email' => 'test2@email.com', 'password' => 'empty']);

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
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow('admin')->to('edit-site');
        $bastion->assign('admin')->to($user);

        $editor = \Ethereal\Bastion\Helper::rolesModel()->create(['name' => 'editor']);
        $bastion->allow($editor)->to('edit-site');
        $bastion->assign($editor)->to($user);

        static::assertTrue($bastion->allows('edit-site'));

        $bastion->retract('admin')->from($user);
        $bastion->retract($editor)->from($user);

        static::assertTrue($bastion->denies('edit-site'));
    }

    public function test_bastion_can_ignore_duplicate_role_assignments()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->assign('admin')->to($user);

        static::assertEquals(1,
            \Ethereal\Bastion\Helper::database()->table(\Ethereal\Bastion\Helper::assignedRolesTable())
                ->where('entity_id', $user->getKey())->count()
        );

        $bastion->assign('admin')->to($user);

        static::assertEquals(1,
            \Ethereal\Bastion\Helper::database()->table(\Ethereal\Bastion\Helper::assignedRolesTable())
                ->where('entity_id', $user->getKey())->count()
        );
    }

    public function test_bastion_can_disallow_abilities_on_roles()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow('admin')->to('edit-site');
        $bastion->disallow('admin')->to('edit-site');
        $bastion->assign('admin')->to($user);

        static::assertTrue($bastion->denies('edit-site'));
    }

    public function test_bastion_can_check_user_roles()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        static::assertTrue($bastion->is($user)->notA('moderator'));
        static::assertTrue($bastion->is($user)->notAn('editor'));
        static::assertFalse($bastion->is($user)->an('admin'));

        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test2@email.com', 'password' => 'empty']));

        $bastion->assign('moderator')->to($user);
        $bastion->assign('editor')->to($user);

        static::assertTrue($bastion->is($user)->a('moderator'));
        static::assertTrue($bastion->is($user)->an('editor'));
        static::assertFalse($bastion->is($user)->notAn('editor'));
        static::assertFalse($bastion->is($user)->an('admin'));
    }

    public function test_bastion_can_check_multiple_user_roles()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        static::assertTrue($bastion->is($user)->notAn('editor', 'moderator'));
        static::assertTrue($bastion->is($user)->notAn('admin', 'moderator'));

        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test2@email.com', 'password' => 'empty']));
        $bastion->assign('moderator')->to($user);
        $bastion->assign('editor')->to($user);

        static::assertTrue($bastion->is($user)->a('subscriber', 'moderator'));
        static::assertTrue($bastion->is($user)->an('admin', 'editor'));
        static::assertTrue($bastion->is($user)->all('editor', 'moderator'));
        static::assertFalse($bastion->is($user)->notAn('editor', 'moderator'));
        static::assertFalse($bastion->is($user)->all('admin', 'moderator'));
    }

    public function test_bastion_can_forbid_and_permit_ability()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));
        $bastion->assign('moderator')->to($user);
        $bastion->allow('moderator')->to('*', '*');

        static::assertTrue($bastion->allows('access-dashboard'));

        $bastion->forbid('moderator')->to('access-dashboard');

        static::assertTrue($bastion->denies('access-dashboard'));

        $bastion->forbid($user)->to('access-tools');

        static::assertTrue($bastion->denies('access-tools'));
        static::assertTrue($bastion->denies('access-dashboard'));

        $bastion->permit($user)->to('access-tools');

        static::assertTrue($bastion->allows('access-tools'));
    }

    public function test_bastion_can_allow_abilities_from_a_defined_callback()
    {
        $user = TestUserModel::random(true);
        $user->smartPush();

        $bastion = $this->bastion($user);
        $bastion->define('edit', function ($user, $profile) {
            if (! $profile instanceof TestProfileModel) {
                return null;
            }

            return $user->id == $profile->user_id;
        });
        static::assertTrue($bastion->allows('edit', new TestProfileModel(['user_id' => $user->id])));
        static::assertFalse($bastion->allows('edit', new TestProfileModel(['user_id' => 99])));
    }
}
