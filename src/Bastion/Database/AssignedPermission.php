<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Database\Ethereal;

class AssignedPermission extends Ethereal
{
    public $timestamps = false;

    protected $casts = [
        'permission_id' => 'int',
        'forbid' => 'bool',
    ];

    protected $columns = ['permission_id', 'model_id', 'model_type', 'forbid'];
}
