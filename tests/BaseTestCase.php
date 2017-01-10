<?php

use Ethereal\Bastion\Rucks;
use Ethereal\Bastion\Bastion;
use Ethereal\Bastion\Store\Store;
use Ethereal\Cache\GroupFileStore;
use Ethereal\Database\Ethereal;
use Illuminate\Container\Container;
use Orchestra\Testbench\TestCase;

class BaseTestCase extends TestCase
{
    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->app['files']->deleteDirectory(__DIR__ . '/storage');

        parent::tearDown();
    }
}
