<?php

namespace Ethereal\Bastion;

use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\ChecksRoles;
use Ethereal\Bastion\Conductors\DeniesAbilities;
use Ethereal\Bastion\Conductors\GivesAbilities;
use Ethereal\Bastion\Conductors\PermitsAbilities;
use Ethereal\Bastion\Conductors\RemovesAbilities;
use Ethereal\Bastion\Conductors\RemovesRoles;
use Ethereal\Bastion\Store\Store;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;

class Bastion
{
    // TODO use full qualified class names in comments

    /**
     * The bouncer clipboard instance.
     *
     * @var Store
     */
    protected $store;

    /**
     * The access gate instance.
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    protected $gate;

    /**
     * Bastion constructor.
     *
     * @param \Illuminate\Contracts\Auth\Access\Gate $gate
     * @param \Ethereal\Bastion\Store\Store $store
     */
    public function __construct(Gate $gate, Store $store)
    {
        $this->gate = $gate;
        $this->store = $store;

        $store->registerAt($gate);
    }

    /**
     * Start a chain, to allow the given authority an ability.
     *
     * @param $authorities
     * @return \Ethereal\Bastion\Conductors\GivesAbilities
     */
    public function allow($authorities)
    {
        return new GivesAbilities($this->getStore(), is_array($authorities) ? $authorities : func_get_args());
    }

    /**
     * Get roles and permissions store.
     *
     * @return \Ethereal\Bastion\Store\Store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Start a chain, to disallow the given authority an ability.
     *
     * @param $authorities
     * @return \Ethereal\Bastion\Conductors\RemovesAbilities
     */
    public function disallow($authorities)
    {
        return new RemovesAbilities($this->getStore(), is_array($authorities) ? $authorities : func_get_args());
    }

    /**
     * Start a chain, to assign the given role to a authority.
     *
     * @param mixed $roles
     * @return \Ethereal\Bastion\Conductors\AssignsRoles
     */
    public function assign($roles)
    {
        return new AssignsRoles($this->getStore(), is_array($roles) ? $roles : func_get_args());
    }

    /**
     * Start a chain, to forbid the given authority an ability.
     *
     * @param $authorities
     * @return \Ethereal\Bastion\Conductors\DeniesAbilities
     */
    public function forbid($authorities)
    {
        return new DeniesAbilities($this->getStore(), is_array($authorities) ? $authorities : func_get_args());
    }

    /**
     * Start a chain, to forbid the given authority an ability.
     *
     * @param $authorities
     * @return \Ethereal\Bastion\Conductors\PermitsAbilities
     */
    public function permit($authorities)
    {
        return new PermitsAbilities($this->getStore(), is_array($authorities) ? $authorities : func_get_args());
    }

    /**
     * Start a chain, to check if the given authority has a certain role.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @return \Ethereal\Bastion\Conductors\ChecksRoles
     */
    public function is(Model $authority)
    {
        return new ChecksRoles($this->getStore(), $authority);
    }

    /**
     * Start a chain, to retract the given role from a authority.
     *
     * @param mixed $roles
     * @return \Ethereal\Bastion\Conductors\RemovesRoles
     */
    public function retract($roles)
    {
        return new RemovesRoles($this->getStore(), is_array($roles) ? $roles : func_get_args());
    }

    /**
     * Determine if the given ability should be granted for the current authority.
     *
     * @param string $ability
     * @param array|mixed $arguments
     * @return bool
     */
    public function allows($ability, $arguments = [])
    {
        return $this->getGate()->allows($ability, $arguments);
    }

    /**
     * @return \Illuminate\Contracts\Auth\Access\Gate
     */
    public function getGate()
    {
        return $this->gate;
    }

    /**
     * @param \Illuminate\Contracts\Auth\Access\Gate $gate
     */
    public function setGate($gate)
    {
        $this->gate = $gate;
    }

    /**
     * Determine if the given ability should be denied for the current authority.
     *
     * @param string $ability
     * @param array|mixed $arguments
     * @return bool
     */
    public function denies($ability, $arguments = [])
    {
        return $this->getGate()->denies($ability, $arguments);
    }

    /**
     * Enable cache.
     */
    public function enableCache()
    {
        $this->store->enableCache();
    }

    /**
     * Disable cache.
     */
    public function disableCache()
    {
        $this->store->disableCache();
    }

    /**
     * Clear cached data for authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     */
    public function refreshFor(Model $authority)
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
     * Define a new ability using a callback.
     *
     * @param string $ability
     * @param callable|string $callback
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function define($ability, $callback)
    {
        $this->getGate()->define($ability, $callback);

        return $this;
    }

    /**
     * Set the bouncer to be the exclusive authority on gate access.
     *
     * @param bool $boolean
     * @return $this
     */
    public function exclusive($boolean = true)
    {
        $this->store->setExclusivity($boolean);

        return $this;
    }
}
