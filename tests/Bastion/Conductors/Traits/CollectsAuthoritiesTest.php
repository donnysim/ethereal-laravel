<?php

namespace Tests\Bastion\Database;

use Ethereal\Bastion\Conductors\Traits\CollectsAuthorities;
use Ethereal\Database\Ethereal;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Models\TestUserModel;

class CollectsAuthoritiesTest extends TestCase
{
    use CollectsAuthorities;

    /**
     * @test
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public function it_returns_array_with_model_if_model_is_passed()
    {
        $model = TestUserModel::create(['email' => 'john@doe.com']);

        self::assertEquals([$model], $this->collectAuthorities($model));
    }

    /**
     * @test
     */
    public function it_returns_the_list_if_array_or_traversable_is_passed()
    {
        $model = new Ethereal();

        self::assertEquals([$model], $this->collectAuthorities([$model]));
    }

    /**
     * @test
     * @expectedException \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public function it_throws_error_if_authority_does_not_exist()
    {
        $this->collectAuthorities(new TestUserModel());
    }

    /**
     * @test
     * @expectedException \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public function it_throws_error_when_authority_by_key_does_not_exist()
    {
        $this->collectAuthorities(TestUserModel::class, [1]);
    }

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [ConsoleServiceProvider::class];
    }

    /**
     * Setup the test environment.
     *
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../../../../migrations/bastion');
        $this->loadMigrationsFrom(__DIR__ . '/../../../migrations');
    }
}
