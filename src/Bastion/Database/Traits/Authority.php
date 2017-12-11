<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\ForbidsPermissions;
use Ethereal\Bastion\Conductors\PermitsPermissions;
use Ethereal\Bastion\Conductors\RemovesPermissions;
use Ethereal\Bastion\Conductors\RemovesRoles;
use Ethereal\Bastion\Database\AssignedPermission;

trait Authority
{
    use HasRoles, HasPermissions;

    /**
     * Give permission to this authority.
     *
     * @param string|array $permissions
     * @param string|\Illuminate\Database\Eloquent\Model|null $model
     * @param int|null $id
     *
     * @return \Ethereal\Bastion\Database\AssignedPermission
     */
    public function allow($permissions, $model = null, $id = null): AssignedPermission
    {
        return \app('bastion')->allow($this)->to((array)$permissions, $model, $id);
    }

    /**
     * Assign roles to this authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @return \Ethereal\Bastion\Conductors\AssignsRoles
     */
    public function assign($roles): AssignsRoles
    {
        return \app('bastion')->assign(\is_array($roles) ? $roles : \func_get_args())->to($this);
    }

    /**
     * Check if authority has permission.
     *
     * @param string|array $permission
     * @param string|\Illuminate\Database\Eloquent\Model|null $model
     *
     * @return bool
     */
    public function can($permission, $model = null): bool
    {
        return \app('bastion')->can($this, $permission, $model);
    }

    /**
     * Check if authority does not have a permission.
     *
     * @param string|array $permission
     * @param string|\Illuminate\Database\Eloquent\Model|null $model
     *
     * @return bool
     */
    public function cannot($permission, $model = null): bool
    {
        return !$this->can($permission, $model);
    }

    /**
     * Remove permission from this authority.
     *
     * @param string|array $permissions
     * @param string|\Illuminate\Database\Eloquent\Model|null $model
     * @param int|null $id
     *
     * @return \Ethereal\Bastion\Conductors\RemovesPermissions
     */
    public function disallow($permissions, $model = null, $id = null): RemovesPermissions
    {
        return \app('bastion')->disallow($this)->to((array)$permissions, $model, $id);
    }

    /**
     * Forbid permission for this authority.
     *
     * @param string|array $permissions
     * @param string|\Illuminate\Database\Eloquent\Model|null $model
     * @param int|null $id
     *
     * @return \Ethereal\Bastion\Conductors\ForbidsPermissions
     */
    public function forbid($permissions, $model = null, $id = null): ForbidsPermissions
    {
        return \app('bastion')->forbid($this)->to((array)$permissions, $model, $id);
    }

    /**
     * Determine if authority has one of the roles.
     *
     * @param array|string $role
     *
     * @return bool
     */
    public function isA($role): bool
    {
        return \app('bastion')->is($this)->a(\is_array($role) ? $role : \func_get_args());
    }

    /**
     * Alias to a method.
     *
     * @param array|string $role
     *
     * @return bool
     */
    public function isAn($role): bool
    {
        return $this->isA(\is_array($role) ? $role : \func_get_args());
    }

    /**
     * Determine if authority does not have one of the roles.
     *
     * @param array|string $role
     *
     * @return bool
     */
    public function isNotA($role): bool
    {
        return \app('bastion')->is($this)->notA(\is_array($role) ? $role : \func_get_args());
    }

    /**
     * Alias to notA method.
     *
     * @param array|string $role
     *
     * @return bool
     */
    public function isNotAn($role): bool
    {
        return $this->isNotA(\is_array($role) ? $role : \func_get_args());
    }

    /**
     * Permit forbidden permissions to this authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @return \Ethereal\Bastion\Conductors\PermitsPermissions
     */
    public function permit($roles): PermitsPermissions
    {
        return \app('bastion')->assign(\is_array($roles) ? $roles : \func_get_args())->to($this);
    }

    /**
     * Remove the given role from authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @return \Ethereal\Bastion\Conductors\RemovesRoles
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function retract($roles): RemovesRoles
    {
        return \app('bastion')->retract(\is_array($roles) ? $roles : \func_get_args())->to($this);
    }
}
