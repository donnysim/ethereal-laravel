<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Exceptions\InvalidAuthorityException;
use Ethereal\Bastion\Helper;

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
     * @return \Ethereal\Bastion\Conductors\AssignsPermissions
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public function to($permissions, $model = null, $id = null): AssignsPermissions
    {
        $authorities = $this->collectAuthorities($this->authorities);

        /** @var \Ethereal\Bastion\Database\Permission $permissionClass */
        $permissionClass = Helper::getPermissionModelClass();
        $keyName = (new $permissionClass)->getKeyName();
        $collection = $permissionClass::ensurePermissions((array)$permissions, $model, $id)->keyBy($keyName);

        if ($collection->isEmpty()) {
            return $this;
        }

        foreach ($authorities as $authority) {
            if (!$authority->exists) {
                throw new InvalidAuthorityException('Cannot assign permissions to authority that does not exist.');
            }

            $missingPermissionKeys = $collection->keys()->diff($permissionClass::ofAuthority($authority)->pluck($keyName));
            foreach ($missingPermissionKeys as $missingPermissionKey) {
                $collection->get($missingPermissionKey)->assignTo($authority);
            }
        }

        $this->store->clearCache();

        return $this;
    }
}
