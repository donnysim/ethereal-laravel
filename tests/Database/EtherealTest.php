<?php

use Ethereal\Database\Ethereal;

class EtherealTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_can_keep_only_specified_relations_and_attributes()
    {
        $model = new Ethereal([
            'id' => 1,
            'email' => 'john@example.com',
        ]);

        $model->setRelation('test', collect());

        static::assertEquals(['id' => 1], $model->only('id')->toArray());
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

        $model->setRelation('test', collect());

        static::assertEquals(['id' => 1, 'test' => []], $model->except('email')->toArray());
    }

    /**
     * @test
     */
    public function it_can_set_attribute_without_morphing()
    {
        $model = new MorphEthereal;
        $model->setAttribute('email', 'test');

        self::assertEquals('not test', $model->email);

        $model->setRawAttribute('email', 'test');

        self::assertEquals('test', $model->email);
    }

    /**
     * @test
     */
    public function it_can_check_if_the_model_is_soft_deleting()
    {
        $model = new Ethereal;
        self::assertFalse($model->isSoftDeleting());

        $model = new MorphEthereal;
        self::assertTrue($model->isSoftDeleting());
    }

    /**
     * @test
     */
    public function it_can_check_if_attribute_is_present()
    {
        $model = new Ethereal;

        self::assertFalse($model->hasAttribute('title'));

        $model->setAttribute('title', 'test');
        self::assertTrue($model->hasAttribute('title'));

        $model->setAttribute('name', null);
        self::assertTrue($model->hasAttribute('name'));
    }

    /**
     * @test
     */
    public function it_can_check_if_all_attributes_are_present()
    {
        $model = new Ethereal;

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
    public function it_can_check_if_one_of_the_attributes_is_present()
    {
        $model = new Ethereal;

        $model->fill([
            'title' => 'test',
            'name' => null,
        ]);

        self::assertTrue($model->hasAttributes(['title'], false));
        self::assertFalse($model->hasAttributes(['email'], false));
        self::assertTrue($model->hasAttributes(['title', 'email'], false));
        self::assertTrue($model->hasAttributes(['email', 'name'], false));
    }
}

class MorphEthereal extends Ethereal
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = 'not ' . $value;
    }
}
