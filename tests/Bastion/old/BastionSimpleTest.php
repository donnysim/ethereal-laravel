<?php

class BastionSimpleTest extends BaseTestCase
{
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
