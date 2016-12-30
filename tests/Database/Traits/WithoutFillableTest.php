<?php

use Ethereal\Database\Ethereal;

class EtherealTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_does_not_have_guarded_or_fillable_attributes()
    {
        $model = new Ethereal;

        self::assertEmpty($model->getFillable());
        self::assertEmpty($model->getGuarded());
    }

    /**
     * @test
     */
    public function is_guarded_always_returns_true()
    {
        $model = new Ethereal;
        $model->guard(['*']);

        self::assertFalse($model->isGuarded('email'));
    }

    /**
     * @test
     */
    public function is_fillable_always_returns_true()
    {
        $model = new Ethereal;
        $model->fillable(['id']);

        self::assertTrue($model->isFillable('email'));
    }

    /**
     * @test
     */
    public function is_fillable_persists_original_behavior_of_not_filling_attributes_starting_with_underscore()
    {
        $model = new Ethereal;
        $model->fillable(['id']);

        self::assertTrue($model->isFillable('email'));
        self::assertFalse($model->isFillable('_email'));
    }

    /**
     * @test
     */
    public function fillable_array_is_not_filtered()
    {
        $values = [
            'id' => 1,
            'title' => 'test',
            'email' => 'john@example.com',
        ];

        $model = new Ethereal;
        $model->fillable(['id', 'title']);
        $model->fill($values);

        self::assertEquals($values, $model->toArray());
    }

    /**
     * @test
     */
    public function totally_guarded_always_reports_false()
    {
        $model = new Ethereal;
        $model->guard(['*']);

        self::assertFalse($model->totallyGuarded());
    }
}
