<?php

trait UsesDatabase
{
    use \Orchestra\Testbench\Traits\WithLoadMigrationsFrom;

    protected function migrate()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->artisan('migrate', [
            '--database' => 'ethereal',
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

    protected function getPackageProviders($app)
    {
        return [\Orchestra\Database\ConsoleServiceProvider::class];
    }
}
