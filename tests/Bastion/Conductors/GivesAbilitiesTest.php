<?php

use Ethereal\Bastion\Conductors\GivesAbilities;
use Ethereal\Bastion\Database\Ability;
use Ethereal\Bastion\Database\Permission;
use Ethereal\Bastion\Store;

class GivesAbilitiesTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     */
    public function it_can_give_ability()
    {
        $this->migrate();

        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner]);
        $allow->to('kick');

        self::assertEquals(1, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => false,
            'group' => null,
            'parent_id' => null,
            'parent_type' => null,
        ])->count());
        self::assertEquals(1, Ability::where([
            'name' => 'kick',
            'entity_id' => null,
            'entity_type' => null,
        ])->count());
    }

    /**
     * @test
     */
    public function it_can_give_ability_against_target()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner]);
        $allow->target($user)->to('kick');

        self::assertEquals(1, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => false,
            'group' => null,
            'parent_id' => null,
            'parent_type' => null,
        ])->count());
        self::assertEquals(1, Ability::where([
            'name' => 'kick',
            'entity_id' => $user->getKey(),
            'entity_type' => $user->getMorphClass(),
        ])->count());
    }

    /**
     * @test
     */
    public function it_can_give_ability_on_group()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner]);
        $allow->target($user)->group('employee')->to('kick');

        self::assertEquals(1, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => false,
            'group' => 'employee',
            'parent_id' => null,
            'parent_type' => null,
        ])->count());
        self::assertEquals(1, Ability::where([
            'name' => 'kick',
            'entity_id' => $user->getKey(),
            'entity_type' => $user->getMorphClass(),
        ])->count());
    }

    /**
     * @test
     */
    public function it_can_give_allow_everything()
    {
        $this->migrate();

        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner]);
        $allow->group('employee')->everything();

        self::assertEquals(1, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => false,
            'group' => 'employee',
            'parent_id' => null,
            'parent_type' => null,
        ])->count());
        self::assertEquals(1, Ability::where([
            'name' => '*',
            'entity_id' => null,
            'entity_type' => '*',
        ])->count());
    }

    /**
     * @test
     */
    public function it_can_give_ability_for_parent()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner]);
        $allow->target($user)->parent($owner)->group('employee')->to('kick');

        self::assertEquals(1, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => false,
            'group' => 'employee',
            'parent_id' => $owner->getKey(),
            'parent_type' => $owner->getMorphClass(),
        ])->count());
        self::assertEquals(1, Ability::where([
            'name' => 'kick',
            'entity_id' => $user->getKey(),
            'entity_type' => $user->getMorphClass(),
        ])->count());
    }
}
