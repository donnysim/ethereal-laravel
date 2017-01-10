<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Helper;

trait HasRoles
{
    /**
     * The roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function roles()
    {
        return $this->morphToMany(Helper::getRoleModelClass(), 'target', Helper::getAssignedRoleTable(), 'target_id', 'role_id');
    }
}
