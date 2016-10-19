<?php

namespace Ethereal\Bastion;

use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\ChecksRoles;
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

    public function allow($authorities)
    {

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
     * Start a chain, to check if the given authority has a certain role.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @return \Ethereal\Bastion\Conductors\ChecksRoles
     */
    public function is(Model $authority)
    {
        return new ChecksRoles($authority, $this->store);
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
     * Get roles and permissions store.
     *
     * @return \Ethereal\Bastion\Store\Store
     */
    public function getStore()
    {
        return $this->store;
    }
}
