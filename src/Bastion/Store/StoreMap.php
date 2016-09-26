<?php

namespace Ethereal\Bastion\Store;

use Ethereal\Bastion\Database\Ability;
use Ethereal\Bastion\Database\Role;
use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Collection;

class StoreMap
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
     * StoreMap constructor.
     * @param $roles
     * @param $abilities
     */
    public function __construct(Collection $roles, Collection $abilities)
    {
        $this->roles = $roles;
        $this->abilities = $abilities;

        $this->updateMap();
    }

    /**
     * Update map values.
     */
    protected function updateMap()
    {
        foreach ($this->roles as $role) {
            /** @var Role $role */
            $level = $role->getLevel();

            if ($level < $this->highestRoleLevel) {
                $this->highestRoleLevel = $level;
            } elseif ($level > $this->lowestRoleLevel) {
                $this->lowestRoleLevel = $level;
            }
        }

        $this->allowedAbilities = $this->abilities->filter(function ($item) {
            /** @var Ability $item */
            return ! $item->isForbidden();
        })->keyBy('identifier');

        $this->forbiddenAbilities = $this->abilities->filter(function ($item) {
            /** @var Ability $item */
            return $item->isForbidden();
        })->keyBy('identifier');
    }

    /**
     * Get abilities.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities()
    {
        return $this->abilities;
    }

    /**
     * Get roles.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Get names of all roles.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRoleNames()
    {
        return $this->roles->pluck('name');
    }

    /**
     * Check if ability is forbidden.
     *
     * @param string $ability
     * @return boolean
     */
    public function forbidden($ability)
    {
        return isset($this->forbiddenAbilities[$ability]);
    }

    /**
     * CHeck if ability is granted.
     *
     * @param string $ability
     * @return bool
     */
    public function granted($ability)
    {
        return isset($this->allowedAbilities[$ability]);
    }

    /**
     * Return deserialized store map.
     *
     * @param array $data
     * @return static
     */
    public static function deserialize(array $data)
    {
        $roles = Helper::getRoleModel()->hydrate($data['roles']);
        $abilities = Helper::getAbilityModel()->hydrate($data['abilities']);

        return new static($roles, $abilities);
    }
}