<?php

namespace Ethereal\Bastion\Conductors;

use Illuminate\Database\Eloquent\Model;

class ChecksAbilities
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
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function can($ability, $model = null)
    {
        return $this->store->hasAbility($this->authority, $ability, $model, $this->scopeParent);
    }
}
