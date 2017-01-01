<?php

trait UsesDatabase
{
    protected function migrate()
    {
        $this->artisan('migrate', [
            '--database' => 'ethereal',
            '--realpath' => __DIR__ . '/migrations',
        ]);
    }

    /**
     * Setup testing environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'ethereal');
        $app['config']->set('database.connections.ethereal', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
