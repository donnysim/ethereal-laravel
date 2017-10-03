<?php

namespace Tests\Models;

use Ethereal\Bastion\Database\Traits\Authority;
use Ethereal\Database\Ethereal;

class TestUserModel extends Ethereal
{
    use Authority;

    protected $table = 'users';
}
