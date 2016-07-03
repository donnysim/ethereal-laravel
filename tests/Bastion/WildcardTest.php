<?php

class BastionSimpleTest extends BaseTestCase
{
    public function testAWildardAbilityAllowsEverything()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*');

        $this->assertTrue($bastion->allows('edit-site'));
        $this->assertTrue($bastion->allows('*'));

        $bastion->disallow($user)->to('*');

        $this->assertTrue($bastion->denies('edit-site'));
        $this->assertTrue($bastion->denies('*'));
    }

    public function testAModelWildardAbilityAllowsAllActionsOnAModel()
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

    public function testAModelBlanketWildardAbilityAllowsAllActionsOnAllItsModels()
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

    public function testAnActionWithAWildcardAllowsTheActionOnAllModels()
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

    public function testDoubleWildcardAllowsEverything()
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

    public function testASimpleWildardAbilityDeniesModelAbilities()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*');

        $this->assertTrue($bastion->denies('edit', $user));
        $this->assertTrue($bastion->denies('edit', User::class));
        $this->assertTrue($bastion->denies('*', $user));
        $this->assertTrue($bastion->denies('*', User::class));
    }

    public function testAModelWildardAbilityDeniesSimpleAbilities()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*', $user);

        $this->assertTrue($bastion->denies('edit'));
        $this->assertTrue($bastion->denies('*'));
    }

    public function testAModelBlanketWildardAbilityDeniesSimpleAbilities()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('*', User::class);

        $this->assertTrue($bastion->denies('*'));
        $this->assertTrue($bastion->denies('edit'));
    }

    public function testAnActionWithAWildcardDeniesSimpleAbilities()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('delete', '*');

        $this->assertTrue($bastion->denies('delete'));
    }
}
