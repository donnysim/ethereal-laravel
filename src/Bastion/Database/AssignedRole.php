<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Database\Ethereal;

class AssignedRole extends Ethereal
{
    protected $columns = ['role_id', 'target_id', 'target_type', 'created_at', 'updated_at'];
}
