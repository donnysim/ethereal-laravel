<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Database\Ethereal;

class Ability extends Ethereal
{
    protected $columns = ['id', 'name', 'entity_id', 'entity_type', 'created_at', 'updated_at'];
}
