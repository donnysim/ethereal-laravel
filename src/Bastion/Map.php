<?php

namespace Ethereal\Bastion;

use Illuminate\Support\Collection;

class Map
{
    /**
     * Permissions collection.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $permissions;

    /**
     * Role collections.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $roles;

    /**
     * Map constructor.
     *
     * @param \Illuminate\Support\Collection $roles
     * @param \Illuminate\Support\Collection $permissions
     */
    public function __construct(Collection $roles, Collection $permissions)
    {
        $this->roles = $roles;
        $this->permissions = $permissions;
    }

    /**
     * Get highest role level.
     *
     * @return int
     */
    public function highestRoleLevel(): int
    {
        return $this->roles->min('level');
    }

    /**
     * Get lowest role level.
     *
     * @return int
     */
    public function lowestRoleLevel(): int
    {
        return $this->roles->max('level');
    }

    /**
     * Get authority permissions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function permissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * Get role names.
     *
     * @return \Illuminate\Support\Collection
     */
    public function roleNames(): Collection
    {
        return $this->roles->pluck('name')->unique();
    }

    /**
     * Get authority roles.
     *
     * @return \Illuminate\Support\Collection
     */
    public function roles(): Collection
    {
        return $this->roles;
    }
}
