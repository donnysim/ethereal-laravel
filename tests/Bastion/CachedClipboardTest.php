<?php

use Ethereal\Bastion\CachedClipboard;
use Ethereal\Bastion\Clipboard;
use Ethereal\Bastion\Helper;
use Illuminate\Cache\ArrayStore;
use Illuminate\Database\Eloquent\Model;

class CachedClipboardTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app->singleton(Clipboard::class, function () {
            return new CachedClipboard(new ArrayStore());
        });
    }

    public function test_it_caches_abilities()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('ban-users');

        $this->assertEquals(['ban-users'], $this->getAbliities($user));

        $bastion->allow($user)->to('create-users');

        $this->assertEquals(['ban-users'], $this->getAbliities($user));
    }

    public function test_it_caches_roles()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->assign('editor')->to($user);

        $this->assertTrue($bastion->is($user)->an('editor'));

        $bastion->assign('moderator')->to($user);

        $this->assertFalse($bastion->is($user)->a('moderator'));
    }

    public function test_it_can_refresh_the_cache()
    {
        $bastion = $this->bastion($user = User::create(['email' => 'test@email.com', 'password' => 'empty']));

        $bastion->allow($user)->to('create-posts');
        $bastion->assign('editor')->to($user);
        $bastion->allow('editor')->to('delete-posts');

        $this->assertEquals(['create-posts', 'delete-posts'], $this->getAbliities($user));

        $bastion->disallow('editor')->to('delete-posts');
        $bastion->allow('editor')->to('edit-posts');

        $this->assertEquals(['create-posts', 'delete-posts'], $this->getAbliities($user));

        $bastion->refresh();

        $this->assertEquals(['create-posts', 'edit-posts'], $this->getAbliities($user));
    }

    public function test_it_can_refresh_the_cache_only_for_one_user()
    {
        $user1 = User::create(['email' => 'test@email.com', 'password' => 'empty']);
        $user2 = User::create(['email' => 'test2@email.com', 'password' => 'empty']);

        $bastion = $this->bastion($user = User::create(['email' => 'test3@email.com', 'password' => 'empty']));

        $bastion->allow('admin')->to('ban-users');
        $bastion->assign('admin')->to($user1);
        $bastion->assign('admin')->to($user2);

        $this->assertEquals(['ban-users'], $this->getAbliities($user1));
        $this->assertEquals(['ban-users'], $this->getAbliities($user2));

        $bastion->disallow('admin')->to('ban-users');
        $bastion->refreshFor($user1);

        $this->assertEquals([], $this->getAbliities($user1));
        $this->assertEquals(['ban-users'], $this->getAbliities($user2));
    }

    /**
     * Get the user's abilities from the given cache instance through the clipboard.
     *
     * @param \Illuminate\Cache\ArrayStore $cache
     * @param \Illuminate\Database\Eloquent\Model $user
     * @return array
     */
    protected function getAbliities(Model $user)
    {
        $abilities = Helper::clipboard()->getAbilities($user)->pluck('name');

        return $abilities->sort()->values()->all();
    }

    /**
     * Get the user's roles from the given cache instance through the clipboard.
     *
     * @param \Illuminate\Cache\ArrayStore $cache
     * @param \Illuminate\Database\Eloquent\Model $user
     * @return array
     */
    protected function getRoles(Model $user)
    {
        return Helper::clipboard()->getRoles($user)->all();
    }
}
