<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Model;

/**
 * @method rolesForAuthority(array $roleIds, Model $authority)
 */
class AssignedRole extends Ethereal
{
    protected $columns = ['role_id', 'target_id', 'target_type', 'created_at', 'updated_at'];

    /**
     * Scope query to roles for authority.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $roleIds
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return mixed
     */
    public function scopeRolesForAuthority($query, array $roleIds, Model $authority)
    {
        return $query->whereIn("{$this->getTable()}.role_id", $roleIds)
            ->where("{$this->getTable()}.target_id", $authority->getKey())
            ->where("{$this->getTable()}.target_type", $authority->getMorphClass());
    }
}
