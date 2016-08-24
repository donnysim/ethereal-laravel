<?php

class WildcardTest extends BaseTestCase
{
    public function test_wildcard_ability_allows_everything()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*');

        static::assertTrue($bastion->allows('edit-site'));
        static::assertTrue($bastion->allows('*'));

        $bastion->disallow($user)->to('*');

        static::assertTrue($bastion->denies('edit-site'));
        static::assertTrue($bastion->denies('*'));
    }

    public function test_model_wildcard_ability_allows_all_actions_on_model()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*', $user);

        static::assertTrue($bastion->allows('*', $user));
        static::assertTrue($bastion->allows('edit', $user));
        static::assertTrue($bastion->denies('*', TestUserModel::class));
        static::assertTrue($bastion->denies('edit', TestUserModel::class));

        $bastion->disallow($user)->to('*', $user);

        static::assertTrue($bastion->denies('*', $user));
        static::assertTrue($bastion->denies('edit', $user));
    }

    public function test_model_blanket_wildcard_ability_allows_all_actions_on_all_its_models()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*', TestUserModel::class);

        static::assertTrue($bastion->allows('*', $user));
        static::assertTrue($bastion->allows('edit', $user));
        static::assertTrue($bastion->allows('*', TestUserModel::class));
        static::assertTrue($bastion->allows('edit', TestUserModel::class));
        static::assertTrue($bastion->denies('edit', TestProfileModel::class));
        static::assertTrue($bastion->denies('edit', TestProfileModel::class));

        $bastion->disallow($user)->to('*', TestUserModel::class);

        static::assertTrue($bastion->denies('*', $user));
        static::assertTrue($bastion->denies('edit', $user));
        static::assertTrue($bastion->denies('*', TestUserModel::class));
        static::assertTrue($bastion->denies('edit', TestUserModel::class));
    }

    public function test_an_action_with_wildcard_allows_the_action_on_all_models()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('delete', '*');

        static::assertTrue($bastion->allows('delete', $user));
        static::assertTrue($bastion->allows('delete', TestUserModel::class));
        static::assertTrue($bastion->allows('delete', '*'));

        $bastion->disallow($user)->to('delete', '*');

        static::assertTrue($bastion->denies('delete', $user));
        static::assertTrue($bastion->denies('delete', TestUserModel::class));
        static::assertTrue($bastion->denies('delete', '*'));
    }

    public function test_double_wildcard_allows_everything()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*', '*');

        static::assertTrue($bastion->allows('*'));
        static::assertTrue($bastion->allows('*', '*'));
        static::assertTrue($bastion->allows('*', $user));
        static::assertTrue($bastion->allows('*', TestUserModel::class));
        static::assertTrue($bastion->allows('ban', '*'));
        static::assertTrue($bastion->allows('ban-users'));
        static::assertTrue($bastion->allows('ban', $user));
        static::assertTrue($bastion->allows('ban', TestUserModel::class));

        $bastion->disallow($user)->to('*', '*');

        static::assertTrue($bastion->denies('*'));
        static::assertTrue($bastion->denies('*', '*'));
        static::assertTrue($bastion->denies('*', $user));
        static::assertTrue($bastion->denies('*', TestUserModel::class));
        static::assertTrue($bastion->denies('ban', '*'));
        static::assertTrue($bastion->denies('ban-users'));
        static::assertTrue($bastion->denies('ban', $user));
        static::assertTrue($bastion->denies('ban', TestUserModel::class));
    }

    public function test_simple_wildcard_ability_denies_model_abilities()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*');

        static::assertTrue($bastion->denies('edit', $user));
        static::assertTrue($bastion->denies('edit', TestUserModel::class));
        static::assertTrue($bastion->denies('*', $user));
        static::assertTrue($bastion->denies('*', TestUserModel::class));
    }

    public function test_model_wildcard_ability_denies_simple_abilities()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*', $user);

        static::assertTrue($bastion->denies('edit'));
        static::assertTrue($bastion->denies('*'));
    }

    public function test_model_blanket_wildcard_ability_denies_simple_abilities()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*', TestUserModel::class);

        static::assertTrue($bastion->denies('*'));
        static::assertTrue($bastion->denies('edit'));
    }

    public function test_an_action_with_wildcard_denies_simple_abilities()
    {
        $bastion = $this->bastion($user = TestUserModel::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('delete', '*');

        static::assertTrue($bastion->denies('delete'));
    }
}
