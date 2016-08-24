<?php

use Ethereal\Database\Ethereal;

class TestRoleModel extends Ethereal
{
    use \Ethereal\Bastion\Traits\Role, \Ethereal\Bastion\Traits\HasAbilities;

    protected $table = 'roles';

    protected $guarded = [];
}