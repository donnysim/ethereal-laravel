<?php

use Ethereal\Bastion\Database\Permission;

class AbilitiesTest extends BaseTestCase
{
    public function test_can_give_and_remove_abilities()
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

    public function test_can_give_and_remove_wildcard_abilities()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*');

        static::assertTrue($bastion->allows('edit-site'));
        static::assertTrue($bastion->allows('ban-users'));
        static::assertTrue($bastion->allows('*'));

        $bastion->disallow($user)->to('*');

        static::assertTrue($bastion->denies('edit-site'));
    }

    public function test_ignores_duplicate_ability_allowances()
    {
        $user1 = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']);
        $user2 = TestUserModel::create(['email' => 'test2@email.com', 'password' => 'empty']);

        $bastion = $this->bastion($user1);

        $bastion->allow($user1)->to('ban-users');
        $bastion->allow($user1)->to('ban-users');

        static::assertEquals(1, Permission::count());

        $bastion->allow($user1)->to('ban', $user2);
        $bastion->allow($user1)->to('ban', $user2);

        static::assertEquals(2, Permission::count());

        $bastion->allow('admin')->to('ban-users');
        $bastion->allow('admin')->to('ban-users');

        static::assertEquals(3, Permission::count());

        $bastion->allow('admin')->to('ban', $user1);
        $bastion->allow('admin')->to('ban', $user1);

        static::assertEquals(4, Permission::count());
    }

    public function test_can_disallow_abilities_on_roles()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));
        $bastion->assign('admin')->to($user);

        $bastion->allow('admin')->to('edit-site');
        static::assertTrue($bastion->allows('edit-site'));

        $bastion->disallow('admin')->to('edit-site');
        static::assertTrue($bastion->denies('edit-site'));
    }

    public function test_defined_closure_abilities_ignore_bastion()
    {
        $user = TestUserModel::random(true);
        $user->smartPush();

        $bastion = $this->bastion($user);
        $bastion->define('edit', function ($user, $profile) {
            return $profile instanceof TestProfileModel && $user->id === $profile->user_id;
        });

        static::assertTrue($bastion->allows('edit', new TestProfileModel(['user_id' => $user->id])));
        static::assertFalse($bastion->allows('edit', new TestProfileModel(['user_id' => 99])));
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
}
