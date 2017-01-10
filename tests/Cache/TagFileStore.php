<?php

class TagFileStore extends BaseTestCase
{
    /**
     * @test
     */
    public function it_can_store_file_using_tags()
    {
        $cache = new \Ethereal\Cache\TagFileStore($this->app['files'], __DIR__ . '/../storage');
        $cache->tags(['bastion', 'user', 1]);
        $cache->put('test', '10', 1);

        self::assertTrue($this->app['files']->isDirectory(__DIR__ . '/../storage/bastion/user/1'));
        self::assertEquals('10', $cache->get('test'));
    }
}
