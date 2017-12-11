<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Exceptions\InvalidAuthorityException;
use Ethereal\Bastion\Helper;

class PermitsPermissions
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
     * PermitsPermissions constructor.
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
     * Permit permissions to one or more authorities.
     *
     * @param $permissions
     * @param string|\Illuminate\Database\Eloquent\Model|null $model
     * @param int|null $id
     *
     * @return \Ethereal\Bastion\Conductors\PermitsPermissions
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     */
    public function to($permissions, $model = null, $id = null): PermitsPermissions
    {
        $authorities = $this->collectAuthorities($this->authorities);

        /** @var \Ethereal\Bastion\Database\Permission $permissionClass */
        $permissionClass = Helper::getPermissionModelClass();
        $keyName = (new $permissionClass)->getKeyName();
        $collection = $permissionClass::ensurePermissions((array)$permissions, $model, $id)->keyBy($keyName);

        if ($collection->isEmpty()) {
            return $this;
        }

        $assignedPermissionModelClass = Helper::getAssignedPermissionModelClass();
        $assignedPermission = new $assignedPermissionModelClass();
        $apTable = $assignedPermission->getTable();
        $query = $assignedPermissionModelClass::query();
        $queries = 0;

        foreach ($authorities as $authority) {
            if (!$authority->exists) {
                throw new InvalidAuthorityException('Cannot assign permissions to authority that does not exist.');
            }

            $query->orWhere(function ($query) use ($keyName, $apTable, $collection, $authority) {
                $query
                    ->whereIn("{$apTable}.permission_id", $collection->pluck($keyName)->all())
                    ->where([
                        "{$apTable}.model_type" => $authority->getMorphClass(),
                        "{$apTable}.model_id" => $authority->getKey(),
                        'forbid' => true,
                    ]);
            });

            $queries++;
        }

        if ($queries > 0) {
            $query->delete();
            $this->store->clearCache();
        }

        return $this;
    }
}
