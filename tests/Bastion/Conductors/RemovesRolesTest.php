<?php

use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\RemovesRoles;
use Ethereal\Bastion\Database\AssignedRole;
use Ethereal\Bastion\Database\Role;
use Ethereal\Bastion\Helper;
use Ethereal\Bastion\Store;

class RemovesRolesTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     */
    public function it_can_retract_roles_from_user()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        (new AssignsRoles(new Store, ['user', 'admin']))->to($user);
        (new RemovesRoles(new Store, ['admin']))->from($user);

        self::assertEquals(1, Role::getRoles($user)->count());
        self::assertEquals('user', Role::getRoles($user)->first()->name);
        self::assertEquals(1, AssignedRole::where('target_id', $user->getKey())
            ->where('target_type', $user->getMorphClass())
            ->count()
        );
    }

    /**
     * @test
     */
    public function it_can_retract_roles_from_model_class_and_ids()
    {
        $this->migrate();

        (new AssignsRoles(new Store, ['user', 'admin']))->to(TestUserModel::class, [1, 2, 3]);
        (new RemovesRoles(new Store, ['admin']))->from(TestUserModel::class, [1, 3]);

        $user = new TestUserModel(['id' => 1]);
        $user->exists = true;

        self::assertEquals(1, Role::getRoles($user)->count());
        self::assertEquals(2, Role::getRoles($user->setAttribute('id', 2))->count());
        self::assertEquals(1, Role::getRoles($user->setAttribute('id', 3))->count());
        self::assertEquals(4, AssignedRole::whereIn('target_id', [1, 2, 3])
            ->where('target_type', Helper::getMorphOfClass(TestUserModel::class))
            ->count()
        );
    }
}
