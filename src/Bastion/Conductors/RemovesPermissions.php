<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use InvalidArgumentException;

class RemovesPermissions
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
     * RemovesPermissions constructor.
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
     * Remove permissions from one or more authorities.
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

        $collection = $permissionClass::ensurePermissions($permissions, $this->store->getGuard(), $model, $id);
        if ($collection->isEmpty()) {
            return $this;
        }

        $assignedPermissionModelClass = Helper::getAssignedPermissionModelClass();
        $assignedPermission = new $assignedPermissionModelClass();
        $query = $assignedPermissionModelClass::query();
        $queries = 0;

        foreach ($authorities as $authority) {
            /** @var \Illuminate\Database\Eloquent\Model $authority */
            if (!$authority->exists) {
                continue;
            }

            $queries++;

            $query->orWhere(function ($query) use ($assignedPermission, $collection, $authority) {
                $query->whereIn("{$assignedPermission->getTable()}.permission_id", $collection->pluck('id')->all())
                    ->where("{$assignedPermission->getTable()}.model_id", $authority->getKey())
                    ->where("{$assignedPermission->getTable()}.model_type", $authority->getMorphClass());
            });
        }

        if ($queries > 0) {
            $query->delete();
            // TODO could be more intelligent, if doesn't include roles, remove from authorities
            $this->store->clearCache();
        }

        return $this;
    }
}
