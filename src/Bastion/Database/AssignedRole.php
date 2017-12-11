<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Database\Ethereal;

class AssignedRole extends Ethereal
{
    public $timestamps = false;

    protected $casts = [
        'role_id' => 'int',
    ];

    protected $columns = ['role_id', 'model_id', 'model_type'];
}
