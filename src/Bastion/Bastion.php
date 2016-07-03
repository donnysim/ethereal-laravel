<?php

namespace Ethereal\Bastion;

use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\ChecksRoles;
use Ethereal\Bastion\Conductors\GivesAbilities;
use Ethereal\Bastion\Conductors\RemovesAbilities;
use Ethereal\Bastion\Conductors\RemovesRoles;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Model;

class Bastion
{
    /**
     * The bouncer clipboard instance.
     *
     * @var Clipboard
     */
    protected $clipboard;

    /**
     * The access gate instance.
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate|null
     */
    protected $gate;

    /**
     * Bastion constructor.
     *
     * @param \Illuminate\Contracts\Auth\Access\Gate|null $gate
     * @param \Ethereal\Bastion\Clipboard $clipboard
     */
    public function __construct(Gate $gate, Clipboard $clipboard)
    {
        $this->gate = $gate;
        $this->clipboard = $clipboard;
    }

    /**
     * Start a chain, to check if the given authority has a certain role.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @return \Ethereal\Bastion\Conductors\ChecksRoles
     */
    public function is(Model $authority)
    {
        return new ChecksRoles($authority, $this->clipboard);
    }

    /**
     * Start a chain, to allow the given role a ability.
     *
     * @param $authorities
     * @return \Ethereal\Bastion\Conductors\GivesAbilities
     */
    public function allow($authorities)
    {
        return new GivesAbilities(func_get_args());
    }

    /**
     * Start a chain, to disallow the given role a ability.
     *
     * @param $authorities
     * @return \Ethereal\Bastion\Conductors\RemovesAbilities
     */
    public function disallow($authorities)
    {
        return new RemovesAbilities(func_get_args());
    }

    /**
     * Start a chain, to assign the given role to a model.
     *
     * @param $roles
     * @return \Ethereal\Bastion\Conductors\AssignsRoles
     */
    public function assign($roles)
    {
        return new AssignsRoles(func_get_args());
    }

    /**
     * Start a chain, to retract the given role from a model.
     *
     * @param $roles
     * @return \Ethereal\Bastion\Conductors\RemovesRoles
     */
    public function retract($roles)
    {
        return new RemovesRoles(func_get_args());
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
     * Get gate instance.
     *
     * @return \Illuminate\Contracts\Auth\Access\Gate|null
     */
    public function getGate()
    {
        return $this->gate;
    }

    /**
     * Set gate instance.
     *
     * @param \Illuminate\Contracts\Auth\Access\Gate|null $gate
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
     * Get clipboard instance.
     *
     * @return \Ethereal\Bastion\Clipboard
     */
    public function getClipboard()
    {
        return $this->clipboard;
    }

    /**
     * Set the bouncer to be the exclusive authority on gate access.
     *
     * @param bool $boolean
     * @return $this
     */
    public function exclusive($boolean = true)
    {
        $this->clipboard->setExclusivity($boolean);

        return $this;
    }

    /**
     * Use the given cache instance.
     *
     * @param \Illuminate\Contracts\Cache\Store $cache
     * @return $this
     */
    public function cache(Store $cache = null)
    {
        $cache = $cache ?: $this->make(CacheRepository::class)->getStore();

        $this->clipboard->setCache($cache);

        return $this;
    }

    /**
     * Clear the cache.
     *
     * @param null|\Illuminate\Database\Eloquent\Model $authority
     * @return $this
     */
    public function refresh(Model $authority = null)
    {
        $this->clipboard->refresh($authority);

        return $this;
    }

    /**
     * Clear the cache for the given authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @return $this
     */
    public function refreshFor(Model $authority)
    {
        $this->clipboard->refreshFor($authority);

        return $this;
    }
}