<?php

namespace Ethereal\Bastion;

use Illuminate\Contracts\Cache\Store as CacheStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        return $this->sear($authority, function () use ($authority) {
            /** @var \Ethereal\Bastion\Database\Role $class */
            $class = Helper::getRoleModelClass();
            $roles = $class::getRoles($authority);

            /** @var \Ethereal\Bastion\Database\Ability $class */
            $class = Helper::getAbilityModelClass();
            $abilities = $class::getAbilities($authority, $roles);

            return new Map($roles, $abilities);
        });
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
        return $this->getMap($authority)->getRoles();
    }

    /**
     * Get authority abilities.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \InvalidArgumentException
     */
    public function getAbilities(Model $authority)
    {
        return $this->getMap($authority)->getAbilities();
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

    /**
     * Check if authority has ability.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|null $model
     * @param string|null $group
     * @param \Illuminate\Database\Eloquent\Model|null $parent
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function hasAbility(Model $authority, $ability, $model = null, $group = null, $parent = null)
    {
        $map = $this->getMap($authority);

        /** @var \Ethereal\Bastion\Database\Ability $abilityClass */
        $abilityClass = Helper::getAbilityModelClass();
        $requested = $abilityClass::compileAbilityIdentifiers($ability, $model, $group, $parent);

        $allows = false;

        foreach ($requested as $identifier) {
            if ($map->isForbidden($identifier)) {
                return false;
            } elseif (!$allows && $map->isAllowed($identifier)) {
                $allows = true;
            }
        }

        return $allows;
    }

    /**
     * Get the cache instance.
     *
     * @return \Illuminate\Contracts\Cache\Store
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Set the cache instance.
     *
     * @param \Illuminate\Contracts\Cache\Store $cache
     *
     * @return $this
     */
    public function setCache(CacheStore $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Clear authority cache.
     */
    public function clearCache()
    {
        $this->cache->flush();
    }

    /**
     * Clear authority cache.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     */
    public function clearCacheFor(Model $authority)
    {
        $this->authorityCache($authority)->forget($this->cacheKey($authority));
    }

    /**
     * Enable cache.
     */
    public function enableCache()
    {
        $this->useCache = true;
    }

    /**
     * Disable cache.
     */
    public function disableCache()
    {
        $this->useCache = false;
    }

    /**
     * Get specific authority cache.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Illuminate\Contracts\Cache\Store|null
     */
    protected function authorityCache(Model $authority)
    {
        $cache = $this->getCache();

        if (!$cache) {
            return null;
        }

        if (method_exists($cache, 'tags')) {
            $cache->tags([$this->tag, $authority->getMorphClass(), $authority->getKey()]);
        }

        return $cache;
    }

    /**
     * Get authority cache key.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return string
     */
    protected function cacheKey(Model $authority)
    {
        return strtolower(Str::slug($authority->getMorphClass() . '-' . $authority->getKey()));
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param callable $callback
     *
     * @return \Ethereal\Bastion\Map
     */
    protected function sear(Model $authority, callable $callback)
    {
        if (!$this->useCache) {
            return $callback();
        }

        $cache = $this->authorityCache($authority);

        if (!$cache) {
            return $callback();
        }

        $cacheKey = $this->cacheKey($authority);
        $value = $cache->get($cacheKey);

        if ($value === null) {
            $value = $callback();
            $cache->forever($cacheKey, $value);
        }

        return $value;
    }
}
