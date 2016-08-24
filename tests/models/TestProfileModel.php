<?php

use Ethereal\Database\Ethereal;

class TestProfileModel extends Ethereal
{
    protected $table = 'profiles';

    protected $guarded = [];

    protected $fillableRelations = ['user'];

    public static function random()
    {
        $faker = \Faker\Factory::create();

        $instance = new static([
            'name' => $faker->name,
            'last_name' => $faker->lastName
        ]);

        return $instance;
    }

    public function user()
    {
        return $this->belongsTo(TestUserModel::class, 'user_id');
    }
}