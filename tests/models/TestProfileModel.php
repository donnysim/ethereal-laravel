<?php

use Ethereal\Database\Ethereal;

class TestProfileModel extends Ethereal
{
    protected $table = 'profiles';

    public function user()
    {
        return $this->belongsTo(TestUserModel::class, 'user_id');
    }

    public function users()
    {
        return $this->belongsToMany(TestUserModel::class, 'profile_user', 'profile_id', 'user_id');
    }
}
