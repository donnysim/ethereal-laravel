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
use Illuminate\Database\Eloquent\Model;

class Bastion
{
    /**
     * The store instance.
     *
     * @var \Ethereal\Bastion\Store\Store
     */
    protected $store;

    /**
     * The access at rucks instance.
     *
     * @var \Ethereal\Bastion\Rucks
     */
    protected $rucks;

    /**
     * Bastion constructor.
     *
     * @param \Ethereal\Bastion\Rucks $rucks
     * @param \Ethereal\Bastion\Store\Store $store
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Rucks $rucks, Store $store)
    {
        $this->rucks = $rucks;
        $this->store = $store;

        $store->registerAt($rucks);
    }

    /**
     * Start a chain, to allow the given authority an ability.
     *
     * @param mixed $authorities
     *
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
     * Get ability and role map.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Ethereal\Bastion\Store\StoreMap
     * @throws \InvalidArgumentException
     */
    public function getMap(Model $authority)
    {
        return $this->getStore()->getMap($authority);
    }

    /**
     * Determine if the given authority has the given ability.
     * This does not check policies or defined abilities.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function can($ability, $model = null)
    {
        return $this->getStore()->check($this->getRucks()->resolveUser(), $ability, $model);
    }

    /**
     * Start a chain, to disallow the given authority an ability.
     *
     * @param mixed $authorities
     *
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
     *
     * @return \Ethereal\Bastion\Conductors\AssignsRoles
     */
    public function assign($roles)
    {
        return new AssignsRoles($this->getStore(), is_array($roles) ? $roles : func_get_args());
    }

    /**
     * Start a chain, to forbid the given authority an ability.
     *
     * @param mixed $authorities
     *
     * @return \Ethereal\Bastion\Conductors\DeniesAbilities
     */
    public function forbid($authorities)
    {
        return new DeniesAbilities($this->getStore(), is_array($authorities) ? $authorities : func_get_args());
    }

    /**
     * Start a chain, to forbid the given authority an ability.
     *
     * @param mixed $authorities
     *
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
     *
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
     *
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
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param array $payload
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function allows($ability, $model = null, $payload = [])
    {
        return $this->getRucks()->allows($ability, $model, $payload);
    }

    /**
     * Get rucks instance.
     *
     * @return \Ethereal\Bastion\Rucks
     */
    public function getRucks()
    {
        return $this->rucks;
    }

    /**
     * Set rucks instance.
     *
     * @param \Ethereal\Bastion\Rucks $rucks
     */
    public function setRucks($rucks)
    {
        $this->rucks = $rucks;
    }

    /**
     * Determine if the given ability should be denied for the current authority.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param array $payload
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function denies($ability, $model = null, $payload = [])
    {
        return $this->getRucks()->denies($ability, $model, $payload);
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
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function define($ability, $callback)
    {
        $this->getRucks()->define($ability, $callback);

        return $this;
    }

    /**
     * Register a callback to run before all checks.
     *
     * @param callable $callback
     * @param bool $prepend
     *
     * @return $this
     */
    public function before(callable $callback, $prepend = false)
    {
        $this->getRucks()->before($callback, $prepend);

        return $this;
    }

    /**
     * Register a callback to run after all checks.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function after(callable $callback)
    {
        $this->getRucks()->after($callback);

        return $this;
    }

    /**
     * Get bastion instance for checking other user.
     *
     * @param \Illuminate\Database\Eloquent\Model $user
     *
     * @return static
     * @throws \InvalidArgumentException
     */
    public function forUser($user)
    {
        return new static($this->rucks->forUser($user), $this->store);
    }
}
