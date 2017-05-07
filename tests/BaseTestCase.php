<?php

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
