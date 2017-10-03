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
     *
     * @return $this
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function to($authorities, array $ids = [])
    {
        $authorities = $this->collectAuthorities($authorities, $ids);

        /** @var \Ethereal\Bastion\Database\Role $roleClass */
        $roleClass = Helper::getRoleModelClass();
        $roles = $roleClass::ensureRoles($this->roles, $this->store->getGuard());

        if ($roles->isEmpty()) {
            return $this;
        }

        foreach ($authorities as $authority) {
            $missingRolesKeys = $roles->keys()->diff($roleClass::ofAuthority($authority)->keys());

            foreach ($missingRolesKeys as $missingRoleId) {
                $roles->get($missingRoleId)->assignTo($authority, $this->store->getGuard());
            }
        }

        $this->store->clearCacheFor($authorities);

        return $this;
    }
}
