<?php

use Ethereal\Bastion\Args;

class ArgsTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_can_resolve_ability()
    {
        $args = new Args('dance');

        self::assertEquals('dance', $args->ability());
    }

    /**
     * @test
     */
    public function it_can_resolve_model_class_and_morph()
    {
        $args = new Args('dance', new TestUserModel);

        self::assertEquals('TestUserModel', $args->modelClass());
        self::assertEquals('TestUserModel', $args->modelMorph());
        self::assertInstanceOf(TestUserModel::class, $args->model());
    }

    /**
     * @test
     */
    public function it_can_resolve_class_and_morph_from_class()
    {
        $args = new Args('dance', TestUserModel::class);

        self::assertEquals('TestUserModel', $args->modelClass());
        self::assertEquals('TestUserModel', $args->modelMorph());
    }

    /**
     * @test
     */
    public function it_can_resolve_payload()
    {
        $args = new Args('dance', TestUserModel::class, ['1', 2]);

        self::assertEquals(['1', 2], $args->payload());
    }

    /**
     * @test
     */
    public function it_accepts_payload_as_second_param_without_model()
    {
        $args = new Args('dance', ['1', 2]);

        self::assertEquals(['1', 2], $args->payload());
    }

    /**
     * @test
     */
    public function it_can_get_expected_policy_method_name()
    {
        $args = new Args('update-create');

        self::assertEquals('updateCreate', $args->method());
    }

    /**
     * @test
     */
    public function it_can_get_arguments_for_policy()
    {
        $model = new TestUserModel;
        $args = new Args('update-create', $model, [1]);

        self::assertEquals([$model, 1], $args->arguments());
    }
}
