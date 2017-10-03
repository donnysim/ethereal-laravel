<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Helper;

trait HasPermissions
{
    /**
     * The permissions relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function permissions()
    {
        return $this->morphToMany(Helper::getPermissionModelClass(), 'model', Helper::getAssignedPermissionsTable(), 'model_id', 'permission_id')
            ->where('guard', \app('bastion')->getGuard());
    }
}
