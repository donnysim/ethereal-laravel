<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Helper;
use Ethereal\Database\Ethereal;

class AssignedRole extends Ethereal
{
    /**
     * Create a new Permission model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        $this->table = Helper::getAssignedRoleTable();

        parent::__construct($attributes);
    }
}