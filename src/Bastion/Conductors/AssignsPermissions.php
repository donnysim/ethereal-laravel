<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use InvalidArgumentException;

class AssignsPermissions
{
    use Traits\CollectsAuthorities;

    /**
     * Authorities to give abilities to.
     *
     * @var array
     */
    protected $authorities = [];

    /**
     * Permission store.
     *
     * @var \Ethereal\Bastion\Store
     */
    protected $store;

    /**
     * AssignsPermissions constructor.
     *
     * @param \Ethereal\Bastion\Store $store
     * @param array $authorities
     */
    public function __construct($store, array $authorities)
    {
        $this->authorities = $authorities;
        $this->store = $store;
    }

    /**
     * Assign permissions to one or more authorities.
     *
     * @param $permissions
     * @param string|\Illuminate\Database\Eloquent\Model|null $model
     * @param int|null $id
     *
     * @return $this
     */
    public function to($permissions, $model = null, $id = null)
    {
        $authorities = $this->collectAuthorities($this->authorities);

        /** @var \Ethereal\Bastion\Database\Permission $permissionClass */
        $permissionClass = Helper::getPermissionModelClass();

        $collection = $permissionClass::ensurePermissions((array)$permissions, $this->store->getGuard(), $model, $id);
        if ($collection->isEmpty()) {
            return $this;
        }

        foreach ($authorities as $authority) {
            /** @var \Illuminate\Database\Eloquent\Model $authority */
            if (!$authority->exists) {
                throw new InvalidArgumentException('Cannot assign permissions for authority that does not exist.');
            }

            $missingPermissionKeys = $collection->keys()->diff($permissionClass::ofAuthority($authority)->keys());

            foreach ($missingPermissionKeys as $missingPermissionKey) {
                $collection->get($missingPermissionKey)->assignTo($authority, $this->store->getGuard());
            }
        }

        $this->store->clearCache();

        return $this;
    }
}
