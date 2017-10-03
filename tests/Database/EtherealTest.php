<?php

namespace Tests\Database;

use Ethereal\Database\Ethereal;
use Orchestra\Testbench\TestCase;

class EtherealTest extends TestCase
{
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

        $model = new Ethereal();
        $model->fillable(['id', 'title']);
        $model->fill($values);

        self::assertEquals($values, $model->toArray());
    }

    /**
     * @test
     */
    public function get_dirty_returns_only_column_values()
    {
        $model = new TestEthereal([
            'name' => 'john',
            'title' => 'old',
        ]);
        $model->syncOriginal();

        self::assertEquals([], $model->getDirty());

        $model->name = 'doe';
        $model->id = 2;

        self::assertEquals(['id' => 2], $model->getDirty());

        $model->title = 'new';

        self::assertEquals([
            'title' => 'new',
            'id' => 2,
        ], $model->getDirty());
    }

    /**
     * @test
     */
    public function is_fillable_always_returns_true()
    {
        $model = new Ethereal();
        $model->fillable(['id']);

        self::assertTrue($model->isFillable('email'));
    }

    /**
     * @test
     */
    public function is_fillable_persists_original_behavior_of_not_filling_attributes_starting_with_underscore()
    {
        $model = new Ethereal();
        $model->fillable(['id']);

        self::assertTrue($model->isFillable('email'));
        self::assertFalse($model->isFillable('_email'));
    }

    /**
     * @test
     */
    public function is_guarded_always_returns_true()
    {
        $model = new Ethereal();
        $model->guard(['*']);

        self::assertFalse($model->isGuarded('email'));
    }

    /**
     * @test
     */
    public function it_can_check_if_all_attributes_are_present()
    {
        $model = new Ethereal();

        self::assertFalse($model->hasAttributes(['title']));

        $model->fill([
            'title' => 'test',
            'name' => null,
        ]);

        self::assertTrue($model->hasAttributes(['title']));
        self::assertFalse($model->hasAttributes(['email']));
        self::assertFalse($model->hasAttributes(['title', 'email']));
        self::assertTrue($model->hasAttributes(['title', 'name']));
    }

    /**
     * @test
     */
    public function it_can_check_if_attribute_is_present()
    {
        $model = new Ethereal();

        self::assertFalse($model->hasAttribute('title'));

        $model->setAttribute('title', 'test');
        self::assertTrue($model->hasAttribute('title'));

        $model->setAttribute('name', null);
        self::assertTrue($model->hasAttribute('name'));
    }

    /**
     * @test
     */
    public function it_can_check_if_one_of_the_attributes_is_present()
    {
        $model = new Ethereal();

        $model->fill([
            'title' => 'test',
            'name' => null,
        ]);

        self::assertTrue($model->hasAttributes(['title'], false));
        self::assertFalse($model->hasAttributes(['email'], false));
        self::assertTrue($model->hasAttributes(['title', 'email'], false));
        self::assertTrue($model->hasAttributes(['email', 'name'], false));
    }

    /**
     * @test
     */
    public function it_can_check_if_the_model_is_soft_deleting()
    {
        $model = new Ethereal();
        self::assertFalse($model->isSoftDeleting());

        $model = new TestEthereal();
        self::assertTrue($model->isSoftDeleting());
    }

    /**
     * @test
     */
    public function it_can_get_model_database_columns()
    {
        $model = new TestEthereal();

        static::assertEquals(['id', 'title'], $model->getColumns());
    }

    /**
     * @test
     */
    public function it_can_keep_all_attributes_and_relations_except_specified_ones()
    {
        $model = new Ethereal([
            'id' => 1,
            'email' => 'john@example.com',
        ]);

        $model->setRelation('test', \collect());

        static::assertEquals(['id' => 1, 'test' => []], $model->keepExcept('email')->toArray());
    }

    /**
     * @test
     */
    public function it_can_keep_only_specified_relations_and_attributes()
    {
        $model = new Ethereal([
            'id' => 1,
            'email' => 'john@example.com',
        ]);

        $model->setRelation('test', \collect());

        static::assertEquals(['id' => 1], $model->keepOnly('id')->toArray());
    }

    /**
     * @test
     */
    public function it_can_set_attribute_without_morphing()
    {
        $model = new TestEthereal();
        $model->setAttribute('email', 'test');

        self::assertEquals('not test', $model->email);

        $model->setRawAttribute('email', 'test');

        self::assertEquals('test', $model->email);
    }

    /**
     * @test
     */
    public function it_can_set_model_database_columns()
    {
        $model = new TestEthereal();
        static::assertEquals(['id', 'title'], $model->getColumns());

        $model->setColumns(['email', 'password']);
        static::assertEquals(['email', 'password'], $model->getColumns());
    }

    /**
     * @test
     */
    public function it_can_set_model_key()
    {
        $model = new Ethereal();
        $model->setKey(1);

        self::assertEquals(1, $model->getAttribute('id'));
    }

    /**
     * @test
     */
    public function it_does_not_have_guarded_or_fillable_attributes()
    {
        $model = new Ethereal();

        self::assertEmpty($model->getFillable());
        self::assertEmpty($model->getGuarded());
    }

    /**
     * @test
     */
    public function totally_guarded_always_reports_false()
    {
        $model = new Ethereal();
        $model->guard(['*']);

        self::assertFalse($model->totallyGuarded());
    }
}

class TestEthereal extends Ethereal
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $columns = ['id', 'title'];

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = 'not ' . $value;
    }
}
