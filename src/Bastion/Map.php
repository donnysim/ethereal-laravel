<?php

namespace Ethereal\Bastion;

use Illuminate\Support\Collection;

class Map
{
    /**
     * Role collections.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $roles;

    /**
     * Ability collection.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $abilities;

    /**
     * Allowed abilities list.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $allowedAbilities;

    /**
     * Forbidden abilities list.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $forbiddenAbilities;

    /**
     * Highest role level. Lower is higher.
     *
     * @var int
     */
    protected $highestRoleLevel = 0;

    /**
     * Lowest role level. Higher is lower.
     *
     * @var int
     */
    protected $lowestRoleLevel = 0;

    /**
     * Map constructor.
     *
     * @param \Illuminate\Support\Collection $roles
     * @param \Illuminate\Support\Collection $abilities
     */
    public function __construct(Collection $roles, Collection $abilities)
    {
        $this->roles = $roles;
        $this->abilities = $abilities;
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
     * Get authority abilities.
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function getAbilities()
    {
        return $this->abilities;
    }

    /**
     * Get role names.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRoleNames()
    {
        return $this->roles->pluck('name');
    }

    /**
     * Get all allowed abilities.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllowedAbilities()
    {
        return $this->abilities->filter(function ($item) {
            return !(bool)$item->forbidden;
        })->keyBy('identifier');
    }

    /**
     * Get all allowed abilities.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getForbiddenAbilities()
    {
        return $this->abilities->filter(function ($item) {
            return (bool)$item->forbidden;
        })->keyBy('identifier');
    }

    /**
     * Determine if the ability is forbidden.
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function isForbidden($identifier)
    {
        return $this->getForbiddenAbilities()->has($identifier);
    }

    /**
     * Determine if the ability is allowed.
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function isAllowed($identifier)
    {
        return $this->getAllowedAbilities()->has($identifier);
    }
}
