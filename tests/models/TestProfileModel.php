<?php

use Ethereal\Database\Ethereal;

class TestProfileModel extends Ethereal
{
    protected $table = 'profiles';

    public function user()
    {
        return $this->belongsTo(TestUserModel::class, 'user_id');
    }
}
