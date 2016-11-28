<?php

namespace Ethereal\Bastion\Store;

use Ethereal\Bastion\RuckArgs;
use Ethereal\Bastion\Rucks;
use Ethereal\Bastion\Database\Ability;
use Ethereal\Bastion\Database\Role;
use Ethereal\Bastion\Helper;
use Illuminate\Contracts\Cache\Store as CacheStore;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Store
{
    /**
     * The tag used for caching.
     *
     * @var string
     */
    protected $tag = 'donnysim-bastion';

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
     * Store constructor.
     *
     * @param \Illuminate\Contracts\Cache\Store $cache
     */
    public function __construct(CacheStore $cache)
    {
        $this->setCache($cache);
    }

    /**
     * Register the store at given rucks instance.
     *
     * @param \Ethereal\Bastion\Rucks $rucks
     *
     * @throws \InvalidArgumentException
     */
    public function registerAt(Rucks $rucks)
    {
        $rucks->before(function ($authority, RuckArgs $args) use ($rucks) {
            // If ability is defined, we let the user handle the rest.
            // Check if class is null is required to prevent checking against policy.
            if ($rucks->has($args->getAbility()) && $args->getClass() === null) {
                return null;
            }

            $modelArg = $args->getModel() ?: $args->getClass();

            if (!$this->check($authority, $args->getAbility(), $modelArg)) {
                return false;
            } elseif (!$rucks->hasPolicyCheck($args->getAbility(), $modelArg)) {
                return true;
            }

            return null;
        });
    }

    /**
     * Determine if the given authority has the given ability.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function check(Model $authority, $ability, $model = null)
    {
        $map = $this->getMap($authority);

        /** @var \Ethereal\Bastion\Database\Ability $abilityClass */
        $abilityClass = Helper::getAbilityModelClass();
        $requested = $abilityClass::compileAbilityIdentifiers($ability, $model);

        $allows = false;

        foreach ($requested as $identifier) {
            if ($map->forbidden($identifier)) {
                return false;
            } elseif (!$allows && $map->granted($identifier)) {
                $allows = true;
            }
        }

        return $allows;
    }

    /**
     * Get authority roles and abilities map.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Ethereal\Bastion\Store\StoreMap
     * @throws \InvalidArgumentException
     */
    public function getMap(Model $authority)
    {
        return $this->sear($this->getCacheKey($authority), function () use ($authority) {
            $roles = $this->getRoles($authority);
            $abilities = $this->getAbilities($authority, $roles);

            return new StoreMap($roles, $abilities);
        });
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param string $key
     * @param callable $callback
     *
     * @return \Ethereal\Bastion\Store\StoreMap
     */
    protected function sear($key, callable $callback)
    {
        if (!$this->useCache) {
            return $callback();
        }

        $value = $this->cache->get($key);

        if ($value === null) {
            $value = $callback();
            $this->cache->forever($key, $value);
        }

        return $value;
    }

    /**
     * Get the cache key for the given model's cache type.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return string
     */
    protected function getCacheKey(Model $authority)
    {
        return Str::slug($authority->getMorphClass()) . '|' . $authority->getKey();
    }

    /**
     * Get roles assigned to authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \InvalidArgumentException
     */
    public function getRoles(Model $authority)
    {
        /** @var Role $class */
        $class = Helper::getRoleModelClass();

        return $class::getRoles($authority);
    }

    /**
     * Get abilities assigned to authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param \Illuminate\Database\Eloquent\Collection|null $roles
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \InvalidArgumentException
     */
    public function getAbilities(Model $authority, Collection $roles = null)
    {
        $roles = $roles ?: $this->getRoles($authority);
        /** @var Ability $class */
        $class = Helper::getAbilityModelClass();

        return $class::getAbilities($authority, $roles);
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
        if (method_exists($cache, 'tags')) {
            $cache = $cache->tags($this->tag);
        }

        $this->cache = $cache;

        return $this;
    }

    /**
     * Clear authority cache.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     */
    public function clearCacheFor(Model $authority)
    {
        $this->cache->forget($this->getCacheKey($authority));
    }

    /**
     * Clear authority cache.
     */
    public function clearCache()
    {
        $this->cache->flush();
    }

    /**
     * Check if an authority has the given roles.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param array|string $roles
     * @param string $boolean
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function checkRole(Model $authority, $roles, $boolean = 'or')
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
}
