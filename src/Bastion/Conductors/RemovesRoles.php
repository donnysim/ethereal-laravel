<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class RemovesRoles
{
    /**
     * List of roles to remove from authority.
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
     * @param \Ethereal\Bastion\Store\Store $store
     * @param string|int|array $roles
     */
    public function __construct($store, $roles)
    {
        $this->roles = $roles;
        $this->store = $store;
    }

    /**
     * Remove roles from provided authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Model[] $authority
     *
     * @throws \InvalidArgumentException
     */
    public function from($authority)
    {
        $authorities = is_array($authority) ? $authority : func_get_args();

        /** @var \Ethereal\Bastion\Database\Role $roleClass */
        $roleClass = Helper::getRoleModelClass();
        $roles = $roleClass::collectRoles($this->roles)->keys()->all();

        $assignedModel = Helper::getAssignedRoleModel();
        $query = $assignedModel->newQuery();

        foreach ($authorities as $auth) {
            /** @var \Illuminate\Database\Eloquent\Model $auth */
            if (!$auth instanceof Model || !$auth->exists) {
                throw new InvalidArgumentException('Cannot assign roles for authority that does not exist.');
            }

            $query->orWhere(function ($query) use ($assignedModel, $auth, $roles) {
                /** @var \Illuminate\Database\Query\Builder $query */
                // TODO move to model?
                $query
                    ->whereIn("{$assignedModel->getTable()}.role_id", $roles)
                    ->where("{$assignedModel->getTable()}.entity_id", $auth->getKey())
                    ->where("{$assignedModel->getTable()}.entity_type", $auth->getMorphClass());
            });

            $this->store->clearCacheFor($auth);
        }

        // TODO what if too many auth users?
        $query->delete();
    }
}
