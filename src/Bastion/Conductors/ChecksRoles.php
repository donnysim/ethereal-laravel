<?php

namespace Ethereal\Bastion\Conductors;

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
     * Permission store.
     *
     * @var \Ethereal\Bastion\Store
     */
    protected $store;

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
        return $this->hasRole($this->authority, \is_array($role) ? $role : \func_get_args());
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
        return $this->hasRole($this->authority, \is_array($role) ? $role : \func_get_args(), 'and');
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
        return $this->a(\is_array($role) ? $role : \func_get_args());
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
        return $this->hasRole($this->authority, \is_array($role) ? $role : \func_get_args(), 'not');
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
        return $this->notA(\is_array($role) ? $role : \func_get_args());
    }

    /**
     * Determine if authority has role.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param string|array $roles
     * @param string $boolean
     *
     * @return bool
     */
    protected function hasRole(Model $authority, $roles, $boolean = 'or')
    {
        $available = $this->store->getMap($authority)->getRoleNames()->intersect($roles);

        if ($boolean === 'or') {
            return $available->count() > 0;
        } elseif ($boolean === 'not') {
            return $available->isEmpty();
        }

        return $available->count() === \count((array)$roles);
    }
}
