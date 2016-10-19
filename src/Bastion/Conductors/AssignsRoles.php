<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class AssignsRoles
{
    /**
     * List of roles to assign to authority.
     *
     * @var array|string
     */
    protected $roles;

    /**
     * Permission store.
     *
     * @var \Ethereal\Bastion\Store\Store
     */
    protected $store;

    /**
     * AssignsRole constructor.
     *
     * @param $store
     * @param string|int|array $roles
     */
    public function __construct($store, $roles)
    {
        $this->roles = $roles;
        $this->store = $store;
    }

    /**
     * Assign roles to authorities.
     *
     * @param Model|Model[] $authority
     */
    public function to($authority)
    {
        $authorities = is_array($authority) ? $authority : func_get_args();

        /** @var \Ethereal\Bastion\Database\Role $roleClass */
        $roleClass = Helper::getRoleModelClass();
        $roles = $roleClass::collectRoles($this->roles);

        /** @var \Ethereal\Bastion\Database\AssignedRole $assignedRoleClass */
        $assignedRoleClass = Helper::getAssignedRoleModelClass();

        foreach ($authorities as $auth) {
            /** @var Model $authority */
            if (! $auth->exists) {
                throw new InvalidArgumentException('Cannot assign roles for authority that does not exist.');
            }

            $existingRoles = $roleClass::getRoles($auth);
            $missingRoles = $roles->keys()->diff($existingRoles->keys());
            $inserts = [];

            foreach ($missingRoles as $missingId) {
                $inserts[] = $roles->get($missingId)->createAssignRecord($auth);
            }

            $assignedRoleClass::insert($inserts);

            $this->store->clearCacheFor($auth);
        }
    }
}
