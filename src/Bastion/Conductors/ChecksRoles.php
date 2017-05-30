<?php

namespace Ethereal\Bastion\Conductors;

use Illuminate\Database\Eloquent\Model;

class ChecksRoles
{
    /**
     * Permission store.
     *
     * @var \Ethereal\Bastion\Store
     */
    protected $store;

    /**
     * The authority against which to check for roles.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $authority;

    /**
     * ChecksRoles constructor.
     *
     * @param \Ethereal\Bastion\Store $store
     * @param \Illuminate\Database\Eloquent\Model $authority
     */
    public function __construct($store, Model $authority)
    {
        $this->store = $store;
        $this->authority = $authority;
    }

    /**
     * Determine if authority has one of the roles.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function a($role)
    {
        $roles = is_array($role) ? $role : func_get_args();

        return $this->store->hasRole($this->authority, $roles, 'or');
    }

    /**
     * Alias to a method.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function an($role)
    {
        return $this->a(is_array($role) ? $role : func_get_args());
    }

    /**
     * Determine if authority does not have one of the roles.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function notA($role)
    {
        $roles = is_array($role) ? $role : func_get_args();

        return $this->store->hasRole($this->authority, $roles, 'not');
    }

    /**
     * Alias to notA method.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function notAn($role)
    {
        return $this->notA(is_array($role) ? $role : func_get_args());
    }

    /**
     * Determine if authority has all of the roles.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function all($role)
    {
        $roles = is_array($role) ? $role : func_get_args();

        return $this->store->hasRole($this->authority, $roles, 'and');
    }
}
