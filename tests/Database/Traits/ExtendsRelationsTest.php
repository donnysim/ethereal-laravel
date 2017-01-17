<?php

use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Collection;

class ExtendsRelationsTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     * @expectedException \Ethereal\Database\Relations\Exceptions\InvalidTypeException
     */
    public function it_checks_if_relation_model_type_is_correct()
    {
        $model = new TestUserModel;
        $model->setRelation('profile', new Ethereal);
    }

    /**
     * @test
     * @expectedException \Ethereal\Database\Relations\Exceptions\InvalidTypeException
     */
    public function it_checks_if_relation_model_types_are_correct()
    {
        $model = new TestUserModel;
        $model->setRelation('profiles', new Collection([
            new TestProfileModel,
            new TestProfileModel,
            new Ethereal
        ]));
    }

    /**
     * @test
     */
    public function it_sets_the_relation_if_type_is_correct()
    {
        $model = new TestUserModel;
        $model->setRelation('profile', new TestProfileModel);

        self::assertInstanceOf(TestProfileModel::class, $model->getRelation('profile'));
    }

    /**
     * @test
     */
    public function extended_relation_handling_can_be_turned_off()
    {
        $model = new ExtendsRelationsEthereal;
        $model->setRelation('profile', new TestUserModel);

        self::assertInstanceOf(TestUserModel::class, $model->getRelation('profile'));
    }

    /**
     * @test
     */
    public function it_automatically_wraps_array_into_model_and_sets_exists_property_depending_on_primary_key()
    {
        $model = new TestUserModel;
        $model->setRelation('profile', ['id' => 1, 'name' => 'John']);

        self::assertInstanceOf(TestProfileModel::class, $model->getRelation('profile'));
        self::assertEquals(['id' => 1, 'name' => 'John'], $model->getRelation('profile')->toArray());
        self::assertTrue($model->getRelation('profile')->exists);
    }

    /**
     * @test
     */
    public function it_automatically_wraps_array_into_model_collection_and_sets_exists_property_depending_on_primary_key()
    {
        $profilesValues = [['id' => 1, 'name' => 'John'], ['name' => 'Jane']];
        $model = new TestUserModel;
        $model->setRelation('profiles', $profilesValues);

        $profiles = $model->getRelation('profiles');

        self::assertInstanceOf(Collection::class, $profiles);

        foreach ($profiles as $index => $profile) {
            self::assertInstanceOf(TestProfileModel::class, $profile);
            self::assertEquals($profilesValues[$index], $profile->toArray());
            self::assertEquals($index === 0, $profile->exists);
        }
    }

    /**
     * @test
     */
    public function it_can_save_and_link_all_relations()
    {
        $this->migrate();

        $model = new TestUserModel(['email' => 'john@example.com']);
        $model->setRelation('profile', ['name' => 'John']);
        $model->smartPush();

        self::assertTrue($model->exists);
        self::assertArraySubset(['id' => 1, 'name' => 'John'], $model->profile()->first()->toArray());
    }

    /**
     * @test
     */
    public function it_wraps_array_into_collection_and_keeps_models()
    {
        $model = new TestUserModel(['email' => 'john@example.com']);
        $model->setRelation('profiles', [new TestProfileModel()]);

        self::assertInstanceOf(Collection::class, $model->profiles);
        self::assertInstanceOf(TestProfileModel::class, $model->profiles->first());
    }
}

class ExtendsRelationsEthereal extends Ethereal
{
    protected $extendedRelations = false;
}
