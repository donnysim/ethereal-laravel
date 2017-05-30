<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use InvalidArgumentException;

class AssignsRoles
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
     * AssignsRoles constructor.
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
     * Assign roles to one or more authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string $authorities
     * @param array $ids
     * @param array $assignAttributes
     *
     * @return $this
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function to($authorities, array $ids = [], array $assignAttributes = [])
    {
        $authorities = $this->collectAuthorities($authorities, $ids);

        /** @var \Ethereal\Bastion\Database\Role $roleClass */
        $roleClass = Helper::getRoleModelClass();
        $roles = $roleClass::collectRoles($this->roles);

        if ($roles->isEmpty()) {
            return $this;
        }

        foreach ($authorities as $authority) {
            /** @var \Illuminate\Database\Eloquent\Model $authority */
            if (!$authority->exists) {
                throw new InvalidArgumentException('Cannot assign roles for authority that does not exist.');
            }

            $missingRolesIds = $roles->keys()->diff($roleClass::getRoles($authority)->keys());

            foreach ($missingRolesIds as $missingRoleId) {
                $roles->get($missingRoleId)->createAssignRecord($authority, $assignAttributes);
            }
        }

        $this->store->clearCacheFor($authorities);

        return $this;
    }
}
