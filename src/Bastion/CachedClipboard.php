<?php

namespace Ethereal\Bastion;

use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CachedClipboard extends Clipboard
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
     * CachedClipboard constructor.
     *
     * @param \Illuminate\Contracts\Cache\Store $cache
     */
    public function __construct(Store $cache)
    {
        $this->setCache($cache);
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
     * @return $this
     */
    public function setCache(Store $cache)
    {
        if (method_exists($cache, 'tags')) {
            $cache = $cache->tags($this->tag);
        }

        $this->cache = $cache;

        return $this;
    }

    /**
     * Get the given authority's abilities.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities(Model $authority)
    {
        $key = $this->getCacheKey($authority, 'abilities');

        if ($abilities = $this->cache->get($key)) {
            return $this->deserializeAbilities($abilities);
        }

        $abilities = parent::getAbilities($authority);

        $this->cache->forever($key, $this->serializeAbilities($abilities));

        return $abilities;
    }

    /**
     * Get the cache key for the given model's cache type.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $type
     * @return string
     */
    protected function getCacheKey(Model $model, $type)
    {
        return implode('-', [
            $this->tag,
            $type,
            $model->getMorphClass(),
            $model->getKey(),
        ]);
    }

    /**
     * Deserialize an array of abilities into a collection of models.
     *
     * @param array $abilities
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function deserializeAbilities(array $abilities)
    {
        return Helper::abilityModel()->hydrate($abilities);
    }

    /**
     * Serialize a collection of ability models into a plain array.
     *
     * @param \Illuminate\Database\Eloquent\Collection $abilities
     * @return array
     */
    protected function serializeAbilities(Collection $abilities)
    {
        return $abilities->map(function ($ability) {
            return $ability->getAttributes();
        })->all();
    }

    /**
     * Get the given authority's roles.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @return \Illuminate\Support\Collection
     */
    public function getRoles(Model $authority)
    {
        $key = $this->getCacheKey($authority, 'roles');

        return $this->sear($key, function () use ($authority) {
            return parent::getRoles($authority);
        });
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    protected function sear($key, callable $callback)
    {
        if (($value = $this->cache->get($key)) === null) {
            $this->cache->forever($key, $value = $callback());
        }

        return $value;
    }

    /**
     * Clear the cache.
     *
     * @param null|\Illuminate\Database\Eloquent\Model $authority
     * @return $this
     */
    public function refresh($authority = null)
    {
        if ($authority !== null) {
            return $this->refreshFor($authority);
        }

        if ($this->cache instanceof TaggedCache) {
            $this->cache->flush();

            return $this;
        }

        return $this->refreshForAllUsersIteratively();
    }

    /**
     * Clear the cache for the given model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return $this
     */
    public function refreshFor(Model $model)
    {
        $this->cache->forget($this->getCacheKey($model, 'abilities'));

        $this->cache->forget($this->getCacheKey($model, 'roles'));

        return $this;
    }

    /**
     * Refresh the cache for all users, iteratively.
     *
     * @return $this
     */
    protected function refreshForAllUsersIteratively()
    {
        foreach ($this->authoritiesIterator() as $authority) {
            $this->refreshFor($authority);
        }

        return $this;
    }

    /**
     * Iterate through all authority objects.
     *
     * @return \Generator
     */
    protected function authoritiesIterator()
    {
        $authorities = Helper::authorities();

        foreach ($authorities as $class) {
            foreach ($class::all() as $authority) {
                yield $authority;
            }
        }
    }
}