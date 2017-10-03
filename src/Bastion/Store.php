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
     * Guard this store is working with.
     *
     * @var string
     */
    protected $guard;

    /**
     * Store constructor.
     *
     * @param string $guard
     */
    public function __construct($guard)
    {
        $this->guard = $guard;
    }

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
        if ($cache = $this->getCache()) {
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
        } elseif ($cache = $this->getCache()) {
            $cache->forget($this->cacheKey($authority));
        }
    }

    /**
     * Get the cache instance.
     *
     * @return \Illuminate\Contracts\Cache\Store
     */
    public function getCache()
    {
        if (!static::$cacheEnabled) {
            return null;
        }

        return $this->cache;
    }

    /**
     * Get guard.
     *
     * @return string
     */
    public function getGuard()
    {
        return $this->guard;
    }

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
            $roles = $class::ofAuthority($authority, $this->guard);

            /** @var \Ethereal\Bastion\Database\Permission $class */
            $class = Helper::getPermissionModelClass();
            $permissions = $class::ofAuthority($authority, $roles, $this->guard);

            return new Map($this->guard, $roles, $permissions);
        });
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
     * Get authority cache key.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return string
     */
    protected function cacheKey(Model $authority)
    {
        return \strtolower(Str::slug($this->guard . '-' . $authority->getMorphClass() . '-' . $authority->getKey()));
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
