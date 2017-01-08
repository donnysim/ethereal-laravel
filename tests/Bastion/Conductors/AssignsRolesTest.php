<?php

use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Database\AssignedRole;
use Ethereal\Bastion\Database\Role;
use Ethereal\Bastion\Helper;
use Ethereal\Bastion\Store;

class AssignsRolesTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     */
    public function it_can_assign_roles_to_user()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $assign = new AssignsRoles(new Store, ['user', 'admin']);

        $assign->to($user);

        self::assertEquals(2, Role::getRoles($user)->count());
        self::assertEquals(2, AssignedRole::where([
            'target_id' => $user->getKey(),
            'target_type' => $user->getMorphClass(),
        ])->count());
    }

    /**
     * @test
     */
    public function it_does_not_add_existing_roles()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $assign = new AssignsRoles(new Store, ['user', 'admin']);

        $assign->to($user);

        self::assertEquals(2, Role::getRoles($user)->count());
        self::assertEquals(2, AssignedRole::where([
            'target_id' => $user->getKey(),
            'target_type' => $user->getMorphClass(),
        ])->count());

        $assign = new AssignsRoles(new Store, ['user', 'admin', 'tester']);
        $assign->to($user);

        self::assertEquals(3, Role::getRoles($user)->count());
        self::assertEquals(3, AssignedRole::where([
            'target_id' => $user->getKey(),
            'target_type' => $user->getMorphClass(),
        ])->count());
    }

    /**
     * @test
     */
    public function it_can_assign_roles_to_model_class_and_ids()
    {
        $this->migrate();

        $assign = new AssignsRoles(new Store, ['user', 'admin']);

        $assign->to(TestUserModel::class, [1, 2, 3]);

        $user = new TestUserModel(['id' => 1]);
        $user->exists = true;

        self::assertEquals(2, Role::getRoles($user)->count());
        self::assertEquals(2, Role::getRoles($user->setAttribute('id', 2))->count());
        self::assertEquals(2, Role::getRoles($user->setAttribute('id', 3))->count());
        self::assertEquals(6, AssignedRole::whereIn('target_id', [1, 2, 3])
            ->where('target_type', Helper::getMorphOfClass(TestUserModel::class))
            ->count()
        );
    }
}
