<?php

namespace Ethereal\Bastion;

use Illuminate\Support\Collection;

class Map
{
    /**
     * Guard name of the map.
     *
     * @var string
     */
    protected $guard;

    /**
     * Permissions collection.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $permissions;

    /**
     * Role collections.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $roles;

    /**
     * Map constructor.
     *
     * @param string $guard
     * @param \Illuminate\Support\Collection $roles
     * @param \Illuminate\Support\Collection $permissions
     */
    public function __construct($guard, Collection $roles, Collection $permissions)
    {
        $this->guard = $guard;
        $this->roles = $roles;
        $this->permissions = $permissions;
    }

    /**
     * Get highest role level.
     *
     * @return int
     */
    public function getHighestRoleLevel()
    {
        return $this->roles->min('level');
    }

    /**
     * Get lowest role level.
     *
     * @return int
     */
    public function getLowestRoleLevel()
    {
        return $this->roles->max('level');
    }

    /**
     * Compile permission identifiers.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPermissionIdentifiers()
    {
        return $this->permissions->map(function ($permission) {
            /** @var \Ethereal\Bastion\Database\Permission $permission */
            return $permission->compileIdentifier();
        });
    }

    /**
     * Get authority abilities.
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function getPermissions()
    {
        return $this->permissions->keyBy('name');
    }

    /**
     * Get role names.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRoleNames()
    {
        return $this->roles->pluck('name')->unique();
    }

    /**
     * Get authority roles.
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Check all roles and see if any of them have a property with specific value.
     *
     * @param string $name
     * @param string|bool|int $value
     *
     * @return bool
     */
    public function has($name, $value)
    {
        foreach ($this->getRoles() as $role) {
            if ($role->{$name} === $value) {
                return true;
            }
        }

        return false;
    }
}
