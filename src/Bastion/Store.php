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
}
