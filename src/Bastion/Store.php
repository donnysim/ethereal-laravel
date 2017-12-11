<?php

namespace Ethereal\Bastion;

use Illuminate\Contracts\Cache\Store as CacheStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Traversable;

class Store
{
    /**
     * Cache status.
     *
     * @var bool
     */
    protected static $cacheEnabled = true;

    /**
     * The cache store.
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $cache;

    /**
     * Disable cache.
     */
    public static function disableCache()
    {
        static::$cacheEnabled = false;
    }

    /**
     * Enable cache.
     */
    public static function enableCache()
    {
        static::$cacheEnabled = true;
    }

    /**
     * Clear authority cache.
     */
    public function clearCache()
    {
        $cache = $this->getCache();
        if ($cache) {
            $cache->flush();
        }
    }

    /**
     * Clear authority cache.
     *
     * @param array|\Illuminate\Database\Eloquent\Model $authority
     */
    public function clearCacheFor($authority)
    {
        if (\is_array($authority) || $authority instanceof Traversable) {
            foreach ($authority as $model) {
                if ($model instanceof Model && $model->exists) {
                    $this->clearCacheFor($model);
                }
            }
        } else {
            $cache = $this->getCache();
            if ($cache) {
                $cache->forget($this->cacheKey($authority));
            }
        }
    }

    /**
     * Get the cache instance.
     *
     * @return \Illuminate\Contracts\Cache\Store|null
     */
    public function getCache()
    {
        if (!static::$cacheEnabled) {
            return null;
        }

        return $this->cache;
    }

    /**
     * Get authority permissions map.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Ethereal\Bastion\Map
     * @throws \InvalidArgumentException
     */
    public function getMap(Model $authority): Map
    {
        return $this->sear($authority, function () use ($authority) {
            /** @var \Ethereal\Bastion\Database\Role $class */
            $class = Helper::getRoleModelClass();
            $roles = $class::allRoles($authority);

            /** @var \Ethereal\Bastion\Database\Permission $class */
            $class = Helper::getPermissionModelClass();
            $permissions = $class::ofAuthority($authority, $roles);

            return new Map($roles, $permissions);
        });
    }

    /**
     * Set the cache instance.
     *
     * @param \Illuminate\Contracts\Cache\Store $cache
     *
     * @return \Ethereal\Bastion\Store
     */
    public function setCache(CacheStore $cache): Store
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Get authority cache key.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return string
     */
    protected function cacheKey(Model $authority): string
    {
        return \strtolower(Str::slug($authority->getMorphClass() . '-' . $authority->getKey()));
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param callable $callback
     *
     * @return \Ethereal\Bastion\Map
     */
    protected function sear(Model $authority, callable $callback): Map
    {
        $cache = $this->getCache();
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
