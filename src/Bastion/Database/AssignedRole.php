<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Model;

/**
 * @method rolesForAuthority(array $roleIds, Model $authority)
 */
class AssignedRole extends Ethereal
{
    use Traits\IsAssignedRole;

    /**
     * Database columns. This is used to filter out invalid columns.
     *
     * @var string[]
     */
    protected $columns = ['role_id', 'target_id', 'target_type', 'created_at', 'updated_at'];
}
