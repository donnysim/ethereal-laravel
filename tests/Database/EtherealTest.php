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
}
