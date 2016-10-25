<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Store\Store;
use Illuminate\Database\Eloquent\Model;

class ChecksRoles
{
    /**
     * The authority against which to check for roles.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $authority;

    /**
     * Bastion store.
     *
     * @var \Ethereal\Bastion\Store\Store
     */
    protected $store;

    /**
     * Constructor.
     *
     * @param \Ethereal\Bastion\Store\Store $store
     * @param \Illuminate\Database\Eloquent\Model $authority
     */
    public function __construct(Store $store, Model $authority)
    {
        $this->authority = $authority;
        $this->store = $store;
    }

    /**
     * Check if the authority has any of the given roles.
     *
     * @param string $role
     *
     * @return bool
     */
    public function a($role)
    {
        $roles = func_get_args();

        return $this->store->checkRole($this->authority, $roles, 'or');
    }

    /**
     * Check if the authority doesn't have any of the given roles.
     *
     * @param string $role
     *
     * @return bool
     */
    public function notA($role)
    {
        $roles = func_get_args();

        return $this->store->checkRole($this->authority, $roles, 'not');
    }

    /**
     * Alias to the "a" method.
     *
     * @param string $role
     *
     * @return bool
     */
    public function an($role)
    {
        $roles = func_get_args();

        return $this->store->checkRole($this->authority, $roles, 'or');
    }

    /**
     * Alias to the "notA" method.
     *
     * @param string $role
     *
     * @return bool
     */
    public function notAn($role)
    {
        $roles = func_get_args();

        return $this->store->checkRole($this->authority, $roles, 'not');
    }

    /**
     * Check if the authority has all of the given roles.
     *
     * @param string $role
     *
     * @return bool
     */
    public function all($role)
    {
        $roles = func_get_args();

        return $this->store->checkRole($this->authority, $roles, 'and');
    }
}
