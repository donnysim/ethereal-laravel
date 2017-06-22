<?php

use Ethereal\Database\Ethereal;

class BuilderTest extends BaseTestCase
{
    use UsesDatabase;

    /**
     * @test
     */
    public function it_supports_array_for_selecting_relation_values()
    {
        $builder = (new Ethereal())->newQuery();
        $builder->with(['test' => ['id', 'title']]);
        $testRelationApply = $builder->getEagerLoads()['test'];
        $testRelationApply($builder);

        self::assertEquals(['id', 'title'], $builder->getQuery()->columns);
    }
}
