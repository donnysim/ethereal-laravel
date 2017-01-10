<?php

namespace Ethereal\Bastion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Store
{
    /**
     * The tag used for caching.
     *
     * @var string
     */
    protected $tag = 'bastion';

    /**
     * The cache store.
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $cache;

    /**
     * Use cache to store query results.
     *
     * @var bool
     */
    protected $useCache = true;

    /**
     * Get authority permissions map.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Ethereal\Bastion\Map
     * @throws \InvalidArgumentException
     */
    public function getMap(Model $authority)
    {
        $roles = $this->getRoles($authority);
        $abilities = $this->getAbilities($authority, $roles);

        return new Map($roles, $abilities);
    }

    /**
     * Get authority roles.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Illuminate\Support\Collection
     * @throws \InvalidArgumentException
     */
    public function getRoles(Model $authority)
    {
        /** @var \Ethereal\Bastion\Database\Role $class */
        $class = Helper::getRoleModelClass();

        return $class::getRoles($authority);
    }

    /**
     * Get authority abilities.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param \Illuminate\Support\Collection|null $roles
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \InvalidArgumentException
     */
    public function getAbilities(Model $authority, Collection $roles = null)
    {
        $roles = $roles ?: $this->getRoles($authority);

        /** @var \Ethereal\Bastion\Database\Ability $class */
        $class = Helper::getAbilityModelClass();

        return $class::getAbilities($authority, $roles);
    }

    /**
     * Check if authority has role.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param array|string $roles
     * @param string $boolean
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function hasRole(Model $authority, $roles, $boolean = 'or')
    {
        $available = $this->getMap($authority)->getRoleNames()->intersect($roles);

        if ($boolean === 'or') {
            return $available->count() > 0;
        } elseif ($boolean === 'not') {
            return $available->isEmpty();
        }

        return $available->count() === count((array)$roles);
    }
}
