<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;

class RemovesRoles
{
    use Traits\CollectsAuthorities;

    /**
     * Roles to assign to the authority.
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
     * RemovesROles constructor.
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
     * @return $this
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function from($authorities, array $ids = [])
    {
        $authorities = $this->collectAuthorities($authorities, $ids);

        /** @var \Ethereal\Bastion\Database\Role $roleClass */
        $roleClass = Helper::getRoleModelClass();
        $roles = $roleClass::ensureRoles($this->roles, $this->store->getGuard());

        if ($roles->isEmpty()) {
            return $this;
        }

        $assignedRoleClass = Helper::getAssignedRoleModelClass();
        $assignedRole = new $assignedRoleClass();
        $query = $assignedRoleClass::query();
        $queries = 0;

        foreach ($authorities as $authority) {
            /** @var \Illuminate\Database\Eloquent\Model $authority */
            if (!$authority->exists) {
                continue;
            }

            $queries++;

            $query->orWhere(function ($query) use ($assignedRole, $roles, $authority) {
                $query->whereIn("{$assignedRole->getTable()}.role_id", $roles->pluck('id')->all())
                    ->where("{$assignedRole->getTable()}.model_id", $authority->getKey())
                    ->where("{$assignedRole->getTable()}.model_type", $authority->getMorphClass());
            });
        }

        if ($queries > 0) {
            $query->delete();
            $this->store->clearCacheFor($authorities);
        }

        return $this;
    }
}
