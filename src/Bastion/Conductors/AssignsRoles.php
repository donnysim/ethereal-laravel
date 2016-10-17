<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Database\AssignedRole;
use Ethereal\Bastion\Database\Role;
use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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
     * AssignsRole constructor.
     *
     * @param string|int|array $roles
     */
    public function __construct($roles)
    {
        $this->roles = is_array($roles) ? $roles : func_get_args();
    }

    public function to($authority)
    {
        $authorities = is_array($authority) ? $authority : func_get_args();

        /** @var Role $roleClass */
        $roleClass = Helper::getRoleModelClass();
        $roles = $roleClass::collectRoles($this->roles);

        /** @var AssignedRole $assignedRoleClass */
        $assignedRoleClass = Helper::getAssignedRoleModelClass();

        foreach ($authorities as $authority) {
            /** @var Model $authority */
            if (! $authority->exists) {
                throw new InvalidArgumentException('Cannot assign roles for authority that does not exist.');
            }

            $existingRoles = $this->getExistingRoles($authority);
            $missingRoles = $roles->keys()->diff($existingRoles->keys());
            $inserts = [];

            foreach ($missingRoles as $missingId) {
                $inserts[] = $roles->get($missingId)->createAssignRecord($authority);
            }

            $assignedRoleClass::insert($inserts);
        }
    }

    /**
     * Get roles assigned to authority.
     *
     * @param Model $authority
     * @return Collection
     */
    protected function getExistingRoles(Model $authority)
    {
        /** @var Role $roleClass */
        $roleClass = Helper::getRoleModelClass();

        return $roleClass::getRoles($authority);
    }
}
