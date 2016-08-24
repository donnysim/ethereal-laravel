<?php

use Ethereal\Database\Ethereal;

class TestCommentModel extends Ethereal
{
    protected $table = 'comments';

    protected $guarded = [];

    protected $fillableRelations = ['user'];

    public function user()
    {
        return $this->belongsTo(TestUserModel::class, 'user_id');
    }
}