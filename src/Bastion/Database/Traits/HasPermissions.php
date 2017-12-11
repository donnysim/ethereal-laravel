<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasPermissions
{
    /**
     * The permissions relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function permissions(): MorphToMany
    {
        return $this->morphToMany(Helper::getPermissionModelClass(), 'model', Helper::getAssignedPermissionsTable(), 'model_id', 'permission_id')
            ->where('guard', \app('bastion')->guard());
    }
}
