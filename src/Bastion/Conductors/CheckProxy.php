<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Model;

class CheckProxy
{
    use Traits\UsesScopes;

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
    public function isA($role)
    {
        return (new ChecksRoles($this->store, $this->authority))->a(is_array($role) ? $role : func_get_args());
    }

    /**
     * Alias to a method.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isAn($role)
    {
        return $this->isA(is_array($role) ? $role : func_get_args());
    }

    /**
     * Determine if authority does not have one of the roles.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isNotA($role)
    {
        return (new ChecksRoles($this->store, $this->authority))->notA(is_array($role) ? $role : func_get_args());
    }

    /**
     * Alias to notA method.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isNotAn($role)
    {
        return $this->isNotA(is_array($role) ? $role : func_get_args());
    }

    /**
     * Determine if authority has all of the roles.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isAll($role)
    {
        return (new ChecksRoles($this->store, $this->authority))->all(is_array($role) ? $role : func_get_args());
    }

    /**
     * Determine if authority has the ability.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function can($ability, $model = null)
    {
        return (new ChecksAbilities($this->store, $this->authority))
            ->group($this->scopeGroup)
            ->parent($this->scopeParent)
            ->can($ability, $model);
    }

    /**
     * Determine if authority does not have the ability.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function cannot($ability, $model = null)
    {
        return !$this->can($ability, $model);
    }

    /**
     * Determine if the ability is allowed for the current user.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null|array $model
     * @param array $payload
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function allowed($ability, $model = null, $payload = [])
    {
        return Helper::bastion()->allows($ability, $model, $payload);
    }

    /**
     * Determine if the ability is denied for the current user.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null|array $model
     * @param array $payload
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function denied($ability, $model = null, $payload = [])
    {
        return !Helper::bastion()->allows($ability, $model, $payload);
    }
}
