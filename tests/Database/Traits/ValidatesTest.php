<?php

use Ethereal\Database\Ethereal;

class ValidatesTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_can_get_validation_rules()
    {
        $model = new ValidatesEthereal;
        $model->rules = ['email' => 'required'];

        self::assertEquals(['email' => 'required'], $model->validationRules());
    }

    /**
     * @test
     */
    public function it_can_create_a_model_validator()
    {
        $model = new ValidatesEthereal;
        $model->rules = ['email' => 'required'];

        $validator = $model->validator();

        self::assertInstanceOf(\Illuminate\Validation\Validator::class, $validator);
    }

    /**
     * @test
     */
    public function it_can_collect_validation_rules()
    {
        $model = new ValidatesEthereal;
        $model->rules = [
            'email' => 'required',
            'fields.*.name' => 'required',
        ];

        self::assertEquals([
            'email' => 'required',
            'fields.*.name' => 'required',
        ], $model->collectValidationRules());
    }

    /**
     * @test
     */
    public function it_can_collect_validation_rules_including_relationship_rules()
    {
        $model = new ValidatesEthereal;
        $model->rules = ['email' => 'required'];

        $single = new ValidatesEthereal;
        $single->rules = ['name' => 'required'];
        $model->setRelation('single', $single);

        $one = new ValidatesEthereal;
        $one->rules = ['title' => 'required'];
        $model->setRelation('multi', collect([$one]));

        $nestedSingle = new ValidatesEthereal;
        $nestedSingle->rules = ['name' => 'required'];
        $single->setRelation('nested', $nestedSingle);
        $one->setRelation('nested', $nestedSingle);
        $one->setRelation('multi', collect([$nestedSingle]));

        $model->setRelation('empty', null);

        static::assertEquals([
            'email' => 'required',
            'single.name' => 'required',
            'single.nested.name' => 'required',
            'multi.*.title' => 'required',
            'multi.*.nested.name' => 'required',
            'multi.*.multi.*.name' => 'required',
        ], $model->collectValidationRules(true));
    }

    /**
     * @test
     */
    public function it_can_collect_validation_data()
    {
        $model = new ValidatesEthereal(['email' => 'john@example.com']);

        self::assertEquals(['email' => 'john@example.com'], $model->collectValidationData());
    }

    /**
     * @test
     */
    public function it_can_collect_validation_data_ignoring_hidden_fields()
    {
        $model = new ValidatesEthereal(['email' => 'john@example.com']);
        $model->setHidden(['email']);

        self::assertEquals(['email' => 'john@example.com'], $model->collectValidationData());
    }

    /**
     * @test
     */
    public function it_can_collect_validation_data_including_relationship_data()
    {
        $model = new ValidatesEthereal(['email' => 'john@example.com']);

        $single = new ValidatesEthereal(['name' => 'John']);
        $model->setRelation('single', $single);

        $one = new ValidatesEthereal(['title' => 'Doe']);
        $model->setRelation('multi', collect([$one]));

        $nestedSingle = new ValidatesEthereal(['name' => 'John']);
        $single->setRelation('nested', $nestedSingle);
        $one->setRelation('nested', $nestedSingle);
        $one->setRelation('multi', collect([$nestedSingle]));

        $model->setRelation('empty', null);

        static::assertEquals([
            'email' => 'john@example.com',
            'single' => [
                'name' => 'John',
                'nested' => ['name' => 'John'],
            ],
            'multi' => [
                [
                    'title' => 'Doe',
                    'nested' => ['name' => 'John'],
                    'multi' => [
                        ['name' => 'John']
                    ]
                ]
            ],
        ], $model->collectValidationData(true));
    }

    /**
     * @test
     */
    public function it_can_check_if_data_is_valid()
    {
        $model = new ValidatesEthereal(['email' => 'john@example.com']);
        $model->rules = [
            'email' => ['required', 'email'],
        ];

        self::assertTrue($model->valid());
    }

    /**
     * @test
     */
    public function it_can_check_if_data_is_invalid()
    {
        $model = new ValidatesEthereal(['email' => 'john']);
        $model->rules = [
            'email' => ['required', 'email'],
        ];

        self::assertTrue($model->invalid());
    }
}

class ValidatesEthereal extends Ethereal
{
    public $rules = [];

    public function validationRules()
    {
        return $this->rules;
    }
}
