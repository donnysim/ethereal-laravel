<?php

use Ethereal\Bastion\Database\AssignedRole;
use Ethereal\Bastion\Database\Role;

class RoleTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     */
    public function it_can_collect_roles_from_names()
    {
        $this->migrate();

        $roles = Role::collectRoles(['user', 'admin']);

        static::assertArraySubset([
            [
                'name' => 'user',
            ],
            [
                'name' => 'admin',
            ],
        ], $roles->values()->toArray());
    }

    /**
     * @test
     */
    public function it_can_collect_roles_from_names_with_custom_attributes()
    {
        $this->migrate();

        $roles = Role::collectRoles(['user' => ['title' => 'Simple User'], 'admin']);

        static::assertArraySubset([
            [
                'name' => 'user',
                'title' => 'Simple User',
            ],
            [
                'name' => 'admin',
            ],
        ], $roles->values()->toArray());
    }

    /**
     * @test
     */
    public function it_can_collect_roles_by_id()
    {
        $this->migrate();

        $role = Role::create(['name' => 'user']);
        $roles = Role::collectRoles([$role->getKey(), 'admin']);

        static::assertArraySubset([
            [
                'id' => $role->getKey(),
                'name' => 'user',
            ],
            [
                'name' => 'admin',
            ],
        ], $roles->values()->toArray());
    }

    /**
     * @test
     */
    public function it_can_collect_roles_with_models()
    {
        $this->migrate();

        $role = Role::create(['name' => 'user']);
        $roles = Role::collectRoles([$role, 'admin']);

        static::assertArraySubset([
            [
                'id' => $role->getKey(),
                'name' => 'user',
            ],
            [
                'name' => 'admin',
            ],
        ], $roles->values()->toArray());
    }

    /**
     * @test
     */
    public function it_can_save_the_model_when_collecting_roles()
    {
        $this->migrate();

        $roles = Role::collectRoles([new Role(['name' => 'user']), 'admin']);

        static::assertArraySubset([
            [
                'name' => 'user',
            ],
            [
                'name' => 'admin',
            ],
        ], $roles->values()->toArray());
    }

    /**
     * @test
     */
    public function it_collects_roles_and_returns_keyed_collection()
    {
        $this->migrate();

        $role1 = Role::create(['name' => 'user']);
        $role2 = Role::create(['name' => 'admin']);
        $roles = Role::collectRoles([$role1, $role2]);

        static::assertArraySubset([
            $role1->getKey() => [
                'id' => $role1->getKey(),
                'name' => 'user',
            ],
            $role2->getKey() => [
                'id' => $role2->getKey(),
                'name' => 'admin',
            ],
        ], $roles->toArray());
    }

    /**
     * @test
     */
    public function it_can_create_assign_record()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $role = Role::create(['name' => 'user']);

        $role->createAssignRecord($user);

        self::assertEquals(1, AssignedRole::where('role_id', $role->getKey())
            ->where('target_id', $user->getKey())
            ->where('target_type', $user->getMorphClass())
            ->count()
        );
    }

    /**
     * @test
     */
    public function it_can_get_all_assigned_roles_for_authority()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $role = Role::create(['name' => 'user']);

        $role->createAssignRecord($user);
    }

    /**
     * @test
     */
    public function it_can_get_all_assigned_roles_of_authority()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $role = Role::create(['name' => 'user']);
        $role2 = Role::create(['name' => 'admin']);

        $role->createAssignRecord($user);
        $role2->createAssignRecord($user);

        self::assertEquals(2, AssignedRole::where('target_id', $user->getKey())
            ->where('target_type', $user->getMorphClass())
            ->count()
        );

        self::assertEquals(2, Role::getRoles($user)->count());
    }
}
