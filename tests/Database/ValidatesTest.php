<?php

use Ethereal\Database\Ethereal;
use Orchestra\Testbench\TestCase;

class ValidatesTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @expectedException \Illuminate\Validation\ValidationException
     */
    public function test_base_validation()
    {
        $model = new ValidationBaseStub();
        static::assertFalse($model->valid());
        static::assertTrue($model->invalid());

        $model = new ValidationBaseStub(['email' => 'myagi@check.yi']);
        static::assertTrue($model->valid());
        static::assertTrue($model->validOrFail());
        static::assertFalse($model->invalid());

        $model = new ValidationBaseStub(['email' => '']);
        $model->validOrFail();
    }

    public function test_fully_valid()
    {
        $model = new ValidationBaseStub(['email' => 'myagi@check.yi']);
        $model->setRelation('profile', new ValidatesProfilesStub([
            'name' => 'Chuck',
        ]));

        static::assertTrue($model->valid());
        static::assertFalse($model->fullyValid());

        $model->setRelation('profile', new ValidatesProfilesStub([
            'name' => 'Chuck',
            'last_name' => 'Norris'
        ]));

        static::assertTrue($model->fullyValid());
    }
}

class ValidationBaseStub extends Ethereal
{
    protected $table = 'users';

    protected $guarded = [];

    public function validationRules()
    {
        return [
            'email' => ['required', 'email']
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(ValidatesRolesStub::class, 'role_user', 'user_id', 'role_id');
    }

    public function profile()
    {
        return $this->hasOne(ValidatesProfilesStub::class, 'user_id', 'id');
    }
}

class ValidatesProfilesStub extends Ethereal
{
    protected $table = 'profiles';

    protected $guarded = [];

    public function validationRules()
    {
        return [
            'name' => 'required',
            'last_name' => 'required',
        ];
    }
}

class ValidatesRolesStub extends Ethereal
{
    protected $table = 'roles';

    protected $guarded = [];
}
