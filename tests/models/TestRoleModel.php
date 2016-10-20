<?php

use Ethereal\Bastion\Database\Traits\HasAbilities;
use Ethereal\Bastion\Database\Traits\IsRole;
use Ethereal\Database\Ethereal;

class TestRoleModel extends Ethereal
{
    use IsRole, HasAbilities;

    protected $table = 'roles';

    protected $guarded = [];
}