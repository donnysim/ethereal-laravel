<?php

namespace Ethereal\Bastion;

use BadMethodCallException;
use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\CheckProxy;
use Ethereal\Bastion\Conductors\ChecksRoles;
use Ethereal\Bastion\Conductors\GivesAbilities;
use Ethereal\Bastion\Conductors\ManageProxy;
use Ethereal\Bastion\Conductors\RemovesAbilities;
use Ethereal\Bastion\Conductors\RemovesRoles;
use Ethereal\Bastion\Conductors\Traits\ManagesAuthority;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;

/**
 * @method policy($model, $policy)
 * @method array policies()
 * @method bool allows($ability, $model = null, $payload = [])
 * @method bool denies($ability, $model = null, $payload = [])
 */
class Bastion
{
    use ManagesAuthority;

    /**
     * Default rucks type to use.
     *
     * @var string
     */
    protected static $type = 'user';

    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Initiated ruck instances.
     *
     * @var array
     */
    protected $rucks = [];

    /**
     * Pass these methods to Rucks.
     *
     * @var array
     */
    protected $passthrough = ['policy', 'policies', 'allows', 'denies'];

    /**
     * Primary store used to get roles and abilities.
     *
     * @var \Ethereal\Bastion\Store
     */
    protected $store;

    /**
     * Create a new rucks instance.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @param \Ethereal\Bastion\Store $store
     */
    public function __construct(Container $container, $store)
    {
        $this->container = $container;
        $this->store = $store;
    }

    /**
     * Start a chain to manage authority roles or abilities.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Ethereal\Bastion\Conductors\ManageProxy
     */
    public function manage(Model $authority)
    {
        return new ManageProxy($this->getStore(), $authority);
    }

    /**
     * Start a chain to check role of authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Ethereal\Bastion\Conductors\ChecksRoles
     */
    public function is(Model $authority)
    {
        return new ChecksRoles($this->getStore(), $authority);
    }

    /**
     * Start a chain to check authority role or ability.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Ethereal\Bastion\Conductors\CheckProxy
     */
    public function check(Model $authority)
    {
        return new CheckProxy($this->getStore(), $authority);
    }

    /**
     * Get authority roles.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Illuminate\Support\Collection
     * @throws \InvalidArgumentException
     */
    public function roles(Model $authority)
    {
        return $this->getStore()->getRoles($authority);
    }

    /**
     * Get authority abilities.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \InvalidArgumentException
     */
    public function abilities(Model $authority)
    {
        return $this->getStore()->getAbilities($authority);
    }

    /**
     * Set default rucks type.
     *
     * @param string $type
     */
    public function useType($type)
    {
        static::$type = $type;
    }

    /**
     * Get store.
     *
     * @return \Ethereal\Bastion\Store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Set store.
     *
     * @param \Ethereal\Bastion\Store $store
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * Enable cache.
     */
    public function enableCache()
    {
        $this->getStore()->enableCache();
    }

    /**
     * Disable cache.
     */
    public function disableCache()
    {
        $this->getStore()->disableCache();
    }

    /**
     * Clear cached data for authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     */
    public function clearCacheFor(Model $authority)
    {
        $this->getStore()->clearCacheFor($authority);
    }

    /**
     * Clear cached data.
     */
    public function clearCache()
    {
        $this->getStore()->clearCache();
    }

    /**
     * Get authority permissions map.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Ethereal\Bastion\Map
     * @throws \InvalidArgumentException
     */
    public function permissions(Model $authority)
    {
        return $this->getStore()->getMap($authority);
    }

    /**
     * Passthrough methods directly to rucks.
     *
     * @param string $name
     * @param array $arguments
     *
     * @throws \BadMethodCallException
     */
    public function __call($name, $arguments)
    {
        if (in_array($name, $this->passthrough, true)) {
            return $this->rucks()->{$name}(...$arguments);
        }

        throw new BadMethodCallException("Method {$name} is not defined.");
    }

    /**
     * Get or initiate a new Rucks instance.
     *
     * @param string|null $type
     *
     * @return \Ethereal\Bastion\Rucks
     */
    public function rucks($type = null)
    {
        if (!$type) {
            $type = static::$type;
        }

        if (!isset($this->rucks[$type])) {
            $this->rucks[$type] = new Rucks($this->container, $this->store);
        }

        return $this->rucks[$type];
    }
}
