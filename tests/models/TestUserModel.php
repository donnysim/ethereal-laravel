<?php

use Ethereal\Bastion\Database\Traits\Authority;
use Ethereal\Database\Ethereal;

class TestUserModel extends Ethereal
{
    protected $table = 'users';

    public function profile()
    {
        return $this->hasOne(TestProfileModel::class, 'user_id');
    }

    public function profiles()
    {
        return $this->hasMany(TestProfileModel::class, 'user_id');
    }

//    public function comments()
//    {
//        return $this->hasMany(TestCommentModel::class, 'user_id');
//    }
//
//    public function rawRoles()
//    {
//        return $this->belongsToMany(TestRoleModel::class, 'role_user', 'user_id', 'role_id');
//    }
}
