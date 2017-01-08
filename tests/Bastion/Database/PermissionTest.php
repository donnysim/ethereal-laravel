<?php

use Ethereal\Bastion\Database\Permission;

class PermissionTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     */
    public function it_can_create_permission_record()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);
        Permission::createPermissionRecord(1, $user);
        self::assertEquals(1, Permission::where([
            'ability_id' => 1,
            'target_id' => $user->getKey(),
            'target_type' => $user->getMorphClass(),
            'forbidden' => false,
            'group' => null,
            'parent_id' => null,
            'parent_type' => null,
        ])->count());

        Permission::createPermissionRecord(1, $user, 'employee');
        self::assertEquals(1, Permission::where([
            'ability_id' => 1,
            'target_id' => $user->getKey(),
            'target_type' => $user->getMorphClass(),
            'forbidden' => false,
            'group' => 'employee',
            'parent_id' => null,
            'parent_type' => null,
        ])->count());

        Permission::createPermissionRecord(1, $user, 'employee', true);
        self::assertEquals(1, Permission::where([
            'ability_id' => 1,
            'target_id' => $user->getKey(),
            'target_type' => $user->getMorphClass(),
            'forbidden' => true,
            'group' => 'employee',
            'parent_id' => null,
            'parent_type' => null,
        ])->count());

        Permission::createPermissionRecord(1, $user, 'employee', true, $owner);
        self::assertEquals(1, Permission::where([
            'ability_id' => 1,
            'target_id' => $user->getKey(),
            'target_type' => $user->getMorphClass(),
            'forbidden' => true,
            'group' => 'employee',
            'parent_id' => $owner->getKey(),
            'parent_type' => $owner->getMorphClass(),
        ])->count());
    }
}
