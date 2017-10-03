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
    public function it_gets_highest_and_lowest_role_level()
    {
        $map = new Map('default', \collect([
            new Role(['level' => 10]),
            new Role(['level' => 100]),
            new Role(['level' => 1000]),
        ]), \collect());

        self::assertEquals($map->getHighestRoleLevel(), 10);
        self::assertEquals($map->getLowestRoleLevel(), 1000);
    }

    /**
     * @test
     */
    public function it_gets_all_role_names()
    {
        $map = new Map('default', \collect([
            new Role(['name' => 'test']),
            new Role(['name' => 'test-2']),
            new Role(['name' => 'test']),
        ]), \collect());

        self::assertEquals(['test', 'test-2'], $map->getRoleNames()->all());
    }

    /**
     * @test
     */
    public function it_gets_permissions()
    {
        $map = new Map('default', \collect(), \collect([
            new Permission(['name' => 'model-permission']),
        ]));

        self::assertEquals(['model-permission'], $map->getPermissions()->keys()->all());
    }
}
