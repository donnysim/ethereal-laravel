<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Database\Ethereal;

class AssignedRole extends Ethereal
{
    public $timestamps = false;

    protected $columns = ['role_id', 'model_id', 'model_type'];
}
