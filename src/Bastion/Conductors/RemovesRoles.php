<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use InvalidArgumentException;

class RemovesRoles
{
    use Traits\CollectsAuthorities;

    /**
     * Roles to remove from the authority.
     *
     * @var array
     */
    protected $roles = [];

    /**
     * Permission store.
     *
     * @var \Ethereal\Bastion\Store
     */
    protected $store;

    /**
     * RemovesRoles constructor.
     *
     * @param \Ethereal\Bastion\Store $store
     * @param array $roles
     */
    public function __construct($store, array $roles)
    {
        $this->roles = $roles;
        $this->store = $store;
    }

    /**
     * Remove roles to one or more authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string $authorities
     * @param array $ids
     *
     * @return $this
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function from($authorities, array $ids = [])
    {
        $authorities = $this->collectAuthorities($authorities, $ids);

        /** @var \Ethereal\Bastion\Database\Role $roleClass */
        $roleClass = Helper::getRoleModelClass();
        $roles = $roleClass::collectRoles($this->roles)->keys()->all();

        if (empty($roles)) {
            return $this;
        }

        $query = Helper::getAssignedRoleModel()->newQuery();

        foreach ($authorities as $authority) {
            /** @var \Illuminate\Database\Eloquent\Model $authority */
            if (!$authority->exists) {
                throw new InvalidArgumentException('Cannot assign roles for authority that does not exist.');
            }

            $query->orWhere(function ($query) use ($roles, $authority) {
                $query->rolesForAuthority($roles, $authority);
            });
        }

        $query->delete();
        $this->store->clearCacheFor($authorities);

        return $this;
    }
}
