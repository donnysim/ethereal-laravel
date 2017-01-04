<?php

use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Collection;

class ExtendsRelationsTest extends BaseTestCase
{
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
}

class ExtendsRelationsEthereal extends Ethereal
{
    protected $extendedRelations = false;
}
