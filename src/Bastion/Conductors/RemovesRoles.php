<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;

class RemovesRoles
{
    use Traits\CollectsAuthorities;

    /**
     * Roles to remove from authority.
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
     * Remove roles from one or more authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string $authorities
     * @param array $ids
     *
     * @return \Ethereal\Bastion\Conductors\RemovesRoles
     * @throws \Ethereal\Bastion\Exceptions\InvalidRoleException
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public function from($authorities, array $ids = []): RemovesRoles
    {
        $authorities = $this->collectAuthorities($authorities, $ids);

        /** @var \Ethereal\Bastion\Database\Role $roleClass */
        $roleClass = Helper::getRoleModelClass();
        $keyName = (new $roleClass)->getKeyName();
        $roles = $roleClass::ensureRoles($this->roles);

        if ($roles->isEmpty()) {
            return $this;
        }

        $assignedRoleClass = Helper::getAssignedRoleModelClass();
        $assignedRoleTable = (new $assignedRoleClass())->getTable();
        $query = $assignedRoleClass::query();
        $queries = 0;

        foreach ($authorities as $authority) {
            $queries++;

            $query->orWhere(function ($query) use ($keyName, $assignedRoleTable, $roles, $authority) {
                $query->whereIn("{$assignedRoleTable}.role_id", $roles->pluck($keyName)->all())
                    ->where("{$assignedRoleTable}.model_id", $authority->getKey())
                    ->where("{$assignedRoleTable}.model_type", $authority->getMorphClass());
            });
        }

        if ($queries > 0) {
            $query->delete();
            $this->store->clearCacheFor($authorities);
        }

        return $this;
    }
}
