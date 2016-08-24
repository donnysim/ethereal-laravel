<?php

use Ethereal\Database\Ethereal;

class TestUserModel extends Ethereal
{
    use \Ethereal\Bastion\Traits\HasAbilities, \Ethereal\Bastion\Traits\HasRoles;

    protected $table = 'users';

    protected $guarded = [];

    protected $fillableRelations = ['profile'];

    public static function random($withProfile = false)
    {
        $faker = \Faker\Factory::create();

        $instance = new static([
            'email' => $faker->email,
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
}