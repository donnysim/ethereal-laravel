<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use InvalidArgumentException;

class AssignsRoles
{
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

    public function to($authorities, array $assignAttributes = [])
    {
        $authorities = is_array($authorities) ? $authorities : func_get_args();

        /** @var \Ethereal\Bastion\Database\Role $roleClass */
        $roleClass = Helper::getRoleModelClass();
        $roles = $roleClass::collectRoles($this->roles);

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

        // TODO clear cache
    }
}
