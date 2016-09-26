<?php

use Ethereal\Database\Ethereal;

class TestUserModel extends Ethereal
{
    protected $table = 'users';

    protected $guarded = [];

    protected $fillableRelations = ['profile'];

    public static function random($withProfile = false)
    {
        $faker = \Faker\Factory::create();

        $instance = new static([
            'email' => $faker->unique()->email,
            'password' => $faker->password,
        ]);

        if ($withProfile) {
            $instance->setRelation('profile', [
                'name' => $faker->name,
                'last_name' => $faker->lastName
            ]);
        }

        return $instance;
    }

    public function profile()
    {
        return $this->hasOne(TestProfileModel::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(TestCommentModel::class, 'user_id');
    }

    public function rawRoles()
    {
        return $this->belongsToMany(TestRoleModel::class, 'role_user', 'user_id', 'role_id');
    }
}