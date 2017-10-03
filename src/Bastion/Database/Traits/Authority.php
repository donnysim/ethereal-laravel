<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Conductors\ChecksPermissions;
use Ethereal\Bastion\Conductors\ChecksRoles;
use Ethereal\Bastion\Conductors\RemovesRoles;

trait Authority
{
    use HasRoles, HasPermissions;

    /**
     * Give permission to this authority.
     *
     * @param string|array $permissions
     * @param string|\Illuminate\Database\Eloquent\Model|null $model
     * @param int|null $id
     */
    public function allow($permissions, $model = null, $id = null)
    {
        \app('bastion')->allow($this)->to((array)$permissions, $model, $id);
    }

    /**
     * Assign roles to this authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     */
    public function assign($roles)
    {
        \app('bastion')->assign(\is_array($roles) ? $roles : \func_get_args())->to($this);
    }

    /**
     * Determine if authority has permission.
     *
     * @param string $permission
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function can($permission, $model = null)
    {
        return (new ChecksPermissions(\app('bastion')->getStore(), $this))->can($permission, $model);
    }

    /**
     * Determine if authority does not have permission.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function cannot($ability, $model = null)
    {
        return !$this->can($ability, $model);
    }

    /**
     * Determine if authority has one of the roles.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isA($role)
    {
        return (new ChecksRoles(\app('bastion')->getStore(), $this))->a(\is_array($role) ? $role : \func_get_args());
    }

    /**
     * Alias to a method.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isAn($role)
    {
        return $this->isA(\is_array($role) ? $role : \func_get_args());
    }

    /**
     * Determine if authority does not have one of the roles.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isNotA($role)
    {
        return (new ChecksRoles(\app('bastion')->getStore(), $this))->notA(\is_array($role) ? $role : \func_get_args());
    }

    /**
     * Alias to notA method.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isNotAn($role)
    {
        return $this->isNotA(\is_array($role) ? $role : \func_get_args());
    }

    /**
     * Remove all roles and assign the given roles to authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function reassign($roles)
    {
        $this->roles()->detach();
        $this->assign(\is_array($roles) ? $roles : \func_get_args());
    }

    /**
     * Remove the given role from authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function retract($roles)
    {
        (new RemovesRoles(\app('bastion')->getStore(), \is_array($roles) ? $roles : \func_get_args()))->from($this);
    }
}
