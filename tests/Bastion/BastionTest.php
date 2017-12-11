<?php

namespace Tests\Bastion\Database;

use Ethereal\Bastion\Bastion;
use Illuminate\Cache\FileStore;
use Orchestra\Testbench\TestCase;

class BastionTest extends TestCase
{
    /**
     * @test
     */
    public function it_allows_setting_guard_without_creating_new_instance()
    {
        $bastion = new Bastion($this->app, null);
        self::assertEquals('default', $bastion->guard());
        $bastion->setGuard('new');
        self::assertEquals('new', $bastion->guard());
    }

    /**
     * @test
     */
    public function it_get_new_instance_for_guard()
    {
        $bastion = new Bastion($this->app, null);
        self::assertEquals('default', $bastion->guard());
        $newInstance = $bastion->forGuard('new');
        self::assertEquals('new', $newInstance->guard());
        self::assertNotEquals($bastion, $newInstance);
    }

    /**
     * @test
     */
    public function it_sets_cache_for_store()
    {
        $cache = new FileStore($this->app['files'], null);
        $bastion = new Bastion($this->app, $cache);
        self::assertEquals($cache, $bastion->store()->getCache());
    }
}
