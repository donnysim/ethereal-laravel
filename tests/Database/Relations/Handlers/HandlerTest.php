<?php

use Ethereal\Database\Ethereal;
use Ethereal\Database\Relations\Exceptions\InvalidTypeException;
use Ethereal\Database\Relations\Manager;
use Illuminate\Database\Eloquent\Collection;

class HandlerTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_can_hydrate_model_from_attributes()
    {
        $parent = new HandlerParent;
        $handler = new RelationHandler($parent->child(), 'child', new Collection([]), Manager::SAVE);

        $model = $handler->hydrateModel(['email' => 'john@example.com']);
        self::assertInstanceOf(HandlerRelation::class, $model);
        self::assertEquals(['email' => 'john@example.com'], $model->toArray());
    }

    /**
     * @test
     */
    public function it_sets_model_existence_depending_on_model_key_presence()
    {
        $parent = new HandlerParent;
        $handler = new RelationHandler($parent->child(), 'child', [], Manager::SAVE);

        $model = $handler->hydrateModel(['id' => 1]);
        self::assertTrue($model->exists);
    }

    /**
     * @test
     */
    public function it_can_hydrate_collection_from_data()
    {
        $data = [
            ['email' => 'john@example.com'],
            ['email' => 'john1@example.com'],
            ['email' => 'john2@example.com'],
        ];

        $parent = new HandlerParent;
        $handler = new RelationHandler($parent->child(), 'child', new Collection, Manager::SAVE);

        $collection = $handler->hydrateCollection($data);
        self::assertInstanceOf(Collection::class, $collection);

        foreach ($collection as $index => $model) {
            self::assertInstanceOf(HandlerRelation::class, $model, "Invalid type model at {$index} index.");
        }

        self::assertEquals($data, $collection->toArray());
    }
}

class HandlerParent extends Ethereal
{
    public function child()
    {
        return $this->hasOne(HandlerRelation::class);
    }
}

class HandlerRelation extends Ethereal
{

}

class RelationHandler extends \Ethereal\Database\Relations\Handlers\Handler
{
    /**
     * Wrap data into model or collection of models based on relation type.
     *
     * @return \Ethereal\Database\Ethereal|\Illuminate\Database\Eloquent\Collection
     */
    public function build()
    {
    }

    /**
     * Save relation data.
     *
     * @return bool
     */
    public function save()
    {
    }

    /**
     * Check if the relation is waiting for parent model to be saved.
     *
     * @return bool
     */
    public function isWaitingForParent()
    {
    }
}
