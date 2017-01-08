<?php

namespace Ethereal\Bastion;

use BadMethodCallException;
use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\GivesAbilities;
use Ethereal\Bastion\Conductors\RemovesRoles;
use Illuminate\Contracts\Container\Container;

/**
 * @method policy($model, $policy)
 * @method array policies()
 */
class Bastion
{
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
    protected $passthrough = ['policy', 'policies'];

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
     * Start a chain to assign the given role to authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @return \Ethereal\Bastion\Conductors\AssignsRoles
     */
    public function assign($roles)
    {
        return new AssignsRoles($this->getStore(), is_array($roles) ? $roles : func_get_args());
    }

    /**
     * Start a chain to remove the given role from authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @return \Ethereal\Bastion\Conductors\RemovesRoles
     */
    public function retract($roles)
    {
        return new RemovesRoles($this->getStore(), is_array($roles) ? $roles : func_get_args());
    }

    /**
     * Start a chain to give abilities to authorities.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $authorities
     *
     * @return \Ethereal\Bastion\Conductors\GivesAbilities
     */
    public function allow($authorities)
    {
        return new GivesAbilities($this->getStore(), is_array($authorities) ? $authorities : func_get_args());
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
