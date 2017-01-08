<?php

use Ethereal\Bastion\Database\Ability;

class AbilityTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     */
    public function it_can_create_ability()
    {
        $this->migrate();

        Ability::createAbilityRecord('test');
        self::assertEquals(1, Ability::where([
            'name' => 'test',
            'entity_id' => null,
            'entity_type' => null,
        ])->count());

        Ability::createAbilityRecord('test', '*');
        self::assertEquals(1, Ability::where([
            'name' => 'test',
            'entity_id' => null,
            'entity_type' => '*',
        ])->count());

        Ability::createAbilityRecord('test', TestUserModel::class);
        self::assertEquals(1, Ability::where([
            'name' => 'test',
            'entity_id' => null,
            'entity_type' => TestUserModel::class,
        ])->count());

        Ability::createAbilityRecord('test', TestUserModel::class, 1);
        self::assertEquals(1, Ability::where([
            'name' => 'test',
            'entity_id' => 1,
            'entity_type' => TestUserModel::class,
        ])->count());

        Ability::createAbilityRecord('test', new TestUserModel(['id' => 2]));
        self::assertEquals(1, Ability::where([
            'name' => 'test',
            'entity_id' => 2,
            'entity_type' => TestUserModel::class,
        ])->count());
    }

    /**
     * @test
     */
    public function it_can_collect_abilities_from_names()
    {
        $this->migrate();

        $abilities = Ability::collectAbilities(['create', 'destroy']);

        static::assertArraySubset([
            [
                'name' => 'create',
            ],
            [
                'name' => 'destroy',
            ],
        ], $abilities->values()->toArray());
    }

    /**
     * @test
     */
    public function it_can_collect_abilities_from_names_with_custom_attributes()
    {
        $this->migrate();

        $abilities = Ability::collectAbilities(['create' => ['title' => 'Create'], 'destroy']);

        static::assertArraySubset([
            [
                'name' => 'create',
                'title' => 'Create',
            ],
            [
                'name' => 'destroy',
            ],
        ], $abilities->values()->toArray());
    }

    /**
     * @test
     */
    public function it_can_collect_abilities_by_id()
    {
        $this->migrate();

        $ability = Ability::createAbilityRecord('create');
        $abilities = Ability::collectAbilities([$ability->getKey(), 'destroy']);

        static::assertArraySubset([
            [
                'name' => 'create',
            ],
            [
                'name' => 'destroy',
            ],
        ], $abilities->values()->toArray());
    }

    /**
     * @test
     */
    public function it_can_collect_abilities_with_models()
    {
        $this->migrate();

        $ability = Ability::createAbilityRecord('create');
        $abilities = Ability::collectAbilities([$ability, 'destroy']);

        static::assertArraySubset([
            [
                'name' => 'create',
            ],
            [
                'name' => 'destroy',
            ],
        ], $abilities->values()->toArray());
    }

    /**
     * @test
     */
    public function it_can_save_abilities_with_models()
    {
        $this->migrate();

        $ability = new Ability(['name' => 'create']);
        $abilities = Ability::collectAbilities([$ability, 'destroy']);

        static::assertArraySubset([
            [
                'name' => 'create',
            ],
            [
                'name' => 'destroy',
            ],
        ], $abilities->values()->toArray());
        self::assertTrue($ability->exists);
    }

    /**
     * @test
     */
    public function it_collects_abilities_and_returns_keyed_collection()
    {
        $this->migrate();

        $ability1 = Ability::create(['name' => 'create']);
        $ability2 = Ability::create(['name' => 'destroy']);
        $abilities = Ability::collectAbilities([$ability1, $ability2]);

        static::assertArraySubset([
            $ability1->getKey() => [
                'id' => $ability1->getKey(),
                'name' => 'create',
            ],
            $ability2->getKey() => [
                'id' => $ability2->getKey(),
                'name' => 'destroy',
            ],
        ], $abilities->toArray());
    }

    /**
     * @test
     */
    public function it_can_find_ability()
    {
        $this->migrate();

        $created = Ability::createAbilityRecord('create');
        $ability = Ability::findAbility('create');

        static::assertArraySubset($created->toArray(), $ability->toArray());
    }

    /**
     * @test
     */
    public function it_can_find_ability_for_model()
    {
        $this->migrate();

        $model = new TestUserModel(['id' => 0]);
        $created = Ability::createAbilityRecord('create', $model);

        $ability = Ability::findAbility('create', $model);
        static::assertArraySubset($created->toArray(), $ability->toArray());

        $ability = Ability::findAbility('create', TestUserModel::class, $model->getKey());
        static::assertArraySubset($created->toArray(), $ability->toArray());
    }
}
