<?php

use Ethereal\Bastion\Database\Traits\Authority;
use Ethereal\Bastion\Database\Traits\HasAbilities;
use Ethereal\Database\Ethereal;

class TestUserModel extends Ethereal
{
    use HasAbilities;

    protected $table = 'users';

    public function profile()
    {
        return $this->hasOne(TestProfileModel::class, 'user_id');
    }

    public function profiles()
    {
        return $this->hasMany(TestProfileModel::class, 'user_id');
    }
}
