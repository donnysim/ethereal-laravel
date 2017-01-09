<?php

use Ethereal\Bastion\Conductors\GivesAbilities;
use Ethereal\Bastion\Conductors\RemovesAbilities;
use Ethereal\Bastion\Database\Ability;
use Ethereal\Bastion\Database\Permission;
use Ethereal\Bastion\Store;

class RemovesAbilitiesTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     */
    public function it_can_remove_ability()
    {
        $this->migrate();

        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner]);
        $allow->to(['kick', 'punch']);

        $remove = new RemovesAbilities(new Store, [$owner]);
        $remove->to('kick');

        self::assertEquals(1, Permission::where([
            'ability_id' => Ability::findAbility('punch')->getKey(),
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => false,
            'group' => null,
            'parent_id' => null,
            'parent_type' => null,
        ])->count());
        // Abilities aren't removed
        self::assertEquals(2, Ability::where([
            'entity_id' => null,
            'entity_type' => null,
        ])->whereIn('name', ['kick', 'punch'])->count());
    }

    /**
     * @test
     */
    public function it_does_not_remove_ability_with_custom_property()
    {
        $this->migrate();

        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner]);
        $allow->group('employee')->to(['kick', 'punch']);

        $remove = new RemovesAbilities(new Store, [$owner]);
        $remove->to('kick');

        self::assertEquals(2, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => false,
            'group' => 'employee',
            'parent_id' => null,
            'parent_type' => null,
        ])->count());
        // Abilities aren't removed
        self::assertEquals(2, Ability::where([
            'entity_id' => null,
            'entity_type' => null,
        ])->whereIn('name', ['kick', 'punch'])->count());
    }

    /**
     * @test
     */
    public function it_can_remove_ability_against_target()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner]);
        $allow->to(['kick', 'punch'], $user);

        $remove = new RemovesAbilities(new Store, [$owner]);
        $remove->to('kick', $user);

        self::assertEquals(1, Permission::where([
            'ability_id' => Ability::findAbility('punch', $user)->getKey(),
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => false,
            'group' => null,
            'parent_id' => null,
            'parent_type' => null,
        ])->count());
        self::assertEquals(2, Ability::where([
            'entity_id' => $user->getKey(),
            'entity_type' => $user->getMorphClass(),
        ])->whereIn('name', ['kick', 'punch'])->count());
    }

    /**
     * @test
     */
    public function it_can_remove_ability_on_group()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner]);
        $allow->group('employee')->to(['kick', 'punch'], $user);

        $remove = new RemovesAbilities(new Store, [$owner]);
        $remove->group('employee')->to('kick', $user);

        self::assertEquals(1, Permission::where([
            'ability_id' => Ability::findAbility('punch', $user)->getKey(),
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => false,
            'group' => 'employee',
            'parent_id' => null,
            'parent_type' => null,
        ])->count());
        self::assertEquals(2, Ability::where([
            'entity_id' => $user->getKey(),
            'entity_type' => $user->getMorphClass(),
        ])->whereIn('name', ['kick', 'punch'])->count());
    }

    /**
     * @test
     */
    public function it_does_not_remove_given_ability_when_no_group_is_specified()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner]);
        $allow->group('employee')->to(['kick', 'punch'], $user);

        $remove = new RemovesAbilities(new Store, [$owner]);
        $remove->to('kick', $user);

        self::assertEquals(2, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => false,
            'group' => 'employee',
            'parent_id' => null,
            'parent_type' => null,
        ])->count());
        self::assertEquals(2, Ability::where([
            'entity_id' => $user->getKey(),
            'entity_type' => $user->getMorphClass(),
        ])->whereIn('name', ['kick', 'punch'])->count());
    }

    /**
     * @test
     */
    public function it_can_remove_allow_everything()
    {
        $this->migrate();

        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner]);
        $allow->group('employee')->everything();

        $remove = new RemovesAbilities(new Store, [$owner]);
        $remove->group('employee')->everything();

        self::assertEquals(0, Permission::where([
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
    public function it_can_remove_ability_for_parent()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner]);
        $allow->parent($owner)->group('employee')->to(['kick', 'punch'], $user);

        $remove = new RemovesAbilities(new Store, [$owner]);
        $remove->parent($owner)->group('employee')->to('kick', $user);

        self::assertEquals(1, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => false,
            'group' => 'employee',
            'parent_id' => $owner->getKey(),
            'parent_type' => $owner->getMorphClass(),
        ])->count());
        self::assertEquals(2, Ability::where([
            'entity_id' => $user->getKey(),
            'entity_type' => $user->getMorphClass(),
        ])->whereIn('name', ['kick', 'punch'])->count());
    }

    /**
     * @test
     */
    public function it_does_not_remove_ability_when_no_parent()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner]);
        $allow->parent($owner)->group('employee')->to(['kick', 'punch'], $user);

        $remove = new RemovesAbilities(new Store, [$owner]);
        $remove->group('employee')->to('kick', $user);

        self::assertEquals(2, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => false,
            'group' => 'employee',
            'parent_id' => $owner->getKey(),
            'parent_type' => $owner->getMorphClass(),
        ])->count());
        self::assertEquals(2, Ability::where([
            'entity_id' => $user->getKey(),
            'entity_type' => $user->getMorphClass(),
        ])->whereIn('name', ['kick', 'punch'])->count());
    }

    /**
     * @test
     */
    public function it_can_permit_forbidden_ability()
    {
        $this->migrate();

        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner], true);
        $allow->to(['kick', 'punch']);

        $remove = new RemovesAbilities(new Store, [$owner], true);
        $remove->to('kick');

        self::assertEquals(1, Permission::where([
            'ability_id' => Ability::findAbility('punch')->getKey(),
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => true,
            'group' => null,
            'parent_id' => null,
            'parent_type' => null,
        ])->count());
        // Abilities aren't removed
        self::assertEquals(2, Ability::where([
            'entity_id' => null,
            'entity_type' => null,
        ])->whereIn('name', ['kick', 'punch'])->count());
    }

    /**
     * @test
     */
    public function it_does_not_permit_forbidden_ability_with_custom_property()
    {
        $this->migrate();

        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner], true);
        $allow->group('employee')->to(['kick', 'punch']);

        $remove = new RemovesAbilities(new Store, [$owner], true);
        $remove->to('kick');

        self::assertEquals(2, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => true,
            'group' => 'employee',
            'parent_id' => null,
            'parent_type' => null,
        ])->count());
        // Abilities aren't removed
        self::assertEquals(2, Ability::where([
            'entity_id' => null,
            'entity_type' => null,
        ])->whereIn('name', ['kick', 'punch'])->count());
    }

    /**
     * @test
     */
    public function it_can_permit_forbidden_ability_against_target()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner], true);
        $allow->to(['kick', 'punch'], $user);

        $remove = new RemovesAbilities(new Store, [$owner], true);
        $remove->to('kick', $user);

        self::assertEquals(1, Permission::where([
            'ability_id' => Ability::findAbility('punch', $user)->getKey(),
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => true,
            'group' => null,
            'parent_id' => null,
            'parent_type' => null,
        ])->count());
        self::assertEquals(2, Ability::where([
            'entity_id' => $user->getKey(),
            'entity_type' => $user->getMorphClass(),
        ])->whereIn('name', ['kick', 'punch'])->count());
    }

    /**
     * @test
     */
    public function it_can_permit_forbidden_ability_on_group()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner], true);
        $allow->group('employee')->to(['kick', 'punch'], $user);

        $remove = new RemovesAbilities(new Store, [$owner], true);
        $remove->group('employee')->to('kick', $user);

        self::assertEquals(1, Permission::where([
            'ability_id' => Ability::findAbility('punch', $user)->getKey(),
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => true,
            'group' => 'employee',
            'parent_id' => null,
            'parent_type' => null,
        ])->count());
        self::assertEquals(2, Ability::where([
            'entity_id' => $user->getKey(),
            'entity_type' => $user->getMorphClass(),
        ])->whereIn('name', ['kick', 'punch'])->count());
    }

    /**
     * @test
     */
    public function it_does_not_permit_forbidden_ability_when_no_group_is_specified()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner], true);
        $allow->group('employee')->to(['kick', 'punch'], $user);

        $remove = new RemovesAbilities(new Store, [$owner], true);
        $remove->to('kick', $user);

        self::assertEquals(2, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => true,
            'group' => 'employee',
            'parent_id' => null,
            'parent_type' => null,
        ])->count());
        self::assertEquals(2, Ability::where([
            'entity_id' => $user->getKey(),
            'entity_type' => $user->getMorphClass(),
        ])->whereIn('name', ['kick', 'punch'])->count());
    }

    /**
     * @test
     */
    public function it_can_permit_forbidden_everything()
    {
        $this->migrate();

        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner], true);
        $allow->group('employee')->everything();

        $remove = new RemovesAbilities(new Store, [$owner], true);
        $remove->group('employee')->everything();

        self::assertEquals(0, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => true,
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
    public function it_can_permit_forbidden_ability_for_parent()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner], true);
        $allow->parent($owner)->group('employee')->to(['kick', 'punch'], $user);

        $remove = new RemovesAbilities(new Store, [$owner], true);
        $remove->parent($owner)->group('employee')->to('kick', $user);

        self::assertEquals(1, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => true,
            'group' => 'employee',
            'parent_id' => $owner->getKey(),
            'parent_type' => $owner->getMorphClass(),
        ])->count());
        self::assertEquals(2, Ability::where([
            'entity_id' => $user->getKey(),
            'entity_type' => $user->getMorphClass(),
        ])->whereIn('name', ['kick', 'punch'])->count());
    }

    /**
     * @test
     */
    public function it_does_not_permit_forbidden_ability_with_parent()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner], true);
        $allow->parent($owner)->group('employee')->to(['kick', 'punch'], $user);

        $remove = new RemovesAbilities(new Store, [$owner], true);
        $remove->group('employee')->to('kick', $user);

        self::assertEquals(2, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => true,
            'group' => 'employee',
            'parent_id' => $owner->getKey(),
            'parent_type' => $owner->getMorphClass(),
        ])->count());
        self::assertEquals(2, Ability::where([
            'entity_id' => $user->getKey(),
            'entity_type' => $user->getMorphClass(),
        ])->whereIn('name', ['kick', 'punch'])->count());
    }

    /**
     * @test
     */
    public function it_does_not_remove_normal_permission_when_forbidden()
    {
        $this->migrate();

        $user = TestUserModel::create(['email' => 'john@example.com']);
        $owner = TestUserModel::create(['email' => 'jane@example.com']);

        $allow = new GivesAbilities(new Store, [$owner]);
        $allow->group('employee')->to(['kick', 'punch'], $user);

        $remove = new RemovesAbilities(new Store, [$owner], true);
        $remove->group('employee')->to('kick', $user);

        self::assertEquals(2, Permission::where([
            'target_id' => $owner->getKey(),
            'target_type' => $owner->getMorphClass(),
            'forbidden' => false,
            'group' => 'employee',
            'parent_id' => null,
            'parent_type' => null
        ])->count());
        self::assertEquals(2, Ability::where([
            'entity_id' => $user->getKey(),
            'entity_type' => $user->getMorphClass(),
        ])->whereIn('name', ['kick', 'punch'])->count());
    }
}
