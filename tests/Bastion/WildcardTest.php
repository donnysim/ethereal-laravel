<?php

class BastionSimpleTest extends BaseTestCase
{
    public function test_awildard_ability_allows_everything()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*');

        $this->assertTrue($bastion->allows('edit-site'));
        $this->assertTrue($bastion->allows('*'));

        $bastion->disallow($user)->to('*');

        $this->assertTrue($bastion->denies('edit-site'));
        $this->assertTrue($bastion->denies('*'));
    }

    public function test_amodel_wildard_ability_allows_all_actions_on_amodel()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*', $user);

        $this->assertTrue($bastion->allows('*', $user));
        $this->assertTrue($bastion->allows('edit', $user));
        $this->assertTrue($bastion->denies('*', User::class));
        $this->assertTrue($bastion->denies('edit', User::class));

        $bastion->disallow($user)->to('*', $user);

        $this->assertTrue($bastion->denies('*', $user));
        $this->assertTrue($bastion->denies('edit', $user));
    }

    public function test_amodel_blanket_wildard_ability_allows_all_actions_on_all_its_models()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*', User::class);

        $this->assertTrue($bastion->allows('*', $user));
        $this->assertTrue($bastion->allows('edit', $user));
        $this->assertTrue($bastion->allows('*', User::class));
        $this->assertTrue($bastion->allows('edit', User::class));
        $this->assertTrue($bastion->denies('edit', Profile::class));
        $this->assertTrue($bastion->denies('edit', Profile::class));

        $bastion->disallow($user)->to('*', User::class);

        $this->assertTrue($bastion->denies('*', $user));
        $this->assertTrue($bastion->denies('edit', $user));
        $this->assertTrue($bastion->denies('*', User::class));
        $this->assertTrue($bastion->denies('edit', User::class));
    }

    public function test_an_action_with_awildcard_allows_the_action_on_all_models()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('delete', '*');

        $this->assertTrue($bastion->allows('delete', $user));
        $this->assertTrue($bastion->allows('delete', User::class));
        $this->assertTrue($bastion->allows('delete', '*'));

        $bastion->disallow($user)->to('delete', '*');

        $this->assertTrue($bastion->denies('delete', $user));
        $this->assertTrue($bastion->denies('delete', User::class));
        $this->assertTrue($bastion->denies('delete', '*'));
    }

    public function test_double_wildcard_allows_everything()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*', '*');

        $this->assertTrue($bastion->allows('*'));
        $this->assertTrue($bastion->allows('*', '*'));
        $this->assertTrue($bastion->allows('*', $user));
        $this->assertTrue($bastion->allows('*', User::class));
        $this->assertTrue($bastion->allows('ban', '*'));
        $this->assertTrue($bastion->allows('ban-users'));
        $this->assertTrue($bastion->allows('ban', $user));
        $this->assertTrue($bastion->allows('ban', User::class));

        $bastion->disallow($user)->to('*', '*');

        $this->assertTrue($bastion->denies('*'));
        $this->assertTrue($bastion->denies('*', '*'));
        $this->assertTrue($bastion->denies('*', $user));
        $this->assertTrue($bastion->denies('*', User::class));
        $this->assertTrue($bastion->denies('ban', '*'));
        $this->assertTrue($bastion->denies('ban-users'));
        $this->assertTrue($bastion->denies('ban', $user));
        $this->assertTrue($bastion->denies('ban', User::class));
    }

    public function test_asimple_wildard_ability_denies_model_abilities()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*');

        $this->assertTrue($bastion->denies('edit', $user));
        $this->assertTrue($bastion->denies('edit', User::class));
        $this->assertTrue($bastion->denies('*', $user));
        $this->assertTrue($bastion->denies('*', User::class));
    }

    public function test_amodel_wildard_ability_denies_simple_abilities()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*', $user);

        $this->assertTrue($bastion->denies('edit'));
        $this->assertTrue($bastion->denies('*'));
    }

    public function test_amodel_blanket_wildard_ability_denies_simple_abilities()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*', User::class);

        $this->assertTrue($bastion->denies('*'));
        $this->assertTrue($bastion->denies('edit'));
    }

    public function test_an_action_with_awildcard_denies_simple_abilities()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('delete', '*');

        $this->assertTrue($bastion->denies('delete'));
    }
}
