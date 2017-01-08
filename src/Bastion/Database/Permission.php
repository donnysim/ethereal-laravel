<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Database\Ethereal;

class Permission extends Ethereal
{
    use Traits\IsPermission;

    protected $columns = ['ability_id', 'target_id', 'target_type', 'parent_id', 'parent_type', 'forbidden', 'group', 'created_at', 'updated_at'];
}
