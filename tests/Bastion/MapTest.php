<?php

namespace Tests\Bastion\Database;

use Ethereal\Bastion\Database\Permission;
use Ethereal\Bastion\Database\Role;
use Ethereal\Bastion\Map;
use Orchestra\Testbench\TestCase;

class MapTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_all_role_names()
    {
        $map = new Map(\collect([
            new Role(['name' => 'test']),
            new Role(['name' => 'test-2']),
            new Role(['name' => 'test']),
        ]), \collect());

        self::assertEquals(['test', 'test-2'], $map->roleNames()->all());
    }

    /**
     * @test
     */
    public function it_gets_highest_and_lowest_role_level()
    {
        $map = new Map(\collect([
            new Role(['level' => 10]),
            new Role(['level' => 100]),
            new Role(['level' => 1000]),
        ]), \collect());

        self::assertEquals($map->highestRoleLevel(), 10);
        self::assertEquals($map->lowestRoleLevel(), 1000);
    }

    /**
     * @test
     */
    public function it_gets_permissions()
    {
        $map = new Map(\collect(), \collect([
            new Permission(['name' => 'model-permission']),
        ]));

        self::assertEquals(['model-permission'], $map->permissions()->pluck('name')->all());
    }
}
