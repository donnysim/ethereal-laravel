<?php

namespace Ethereal\Bastion\Traits;

use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\RemovesRoles;
use Ethereal\Bastion\Helper;

trait HasRoles
{
    /**
     * The roles relationship.
     *
     * @return mixed
     */
    public function roles()
    {
        return $this->morphToMany(Helper::rolesModelClass(), 'entity', Helper::assignedRolesTable(), 'entity_id', 'role_id');
    }

    /**
     * Assign the given roles to the model.
     *
     * @param string|int|array $roles
     * @return $this
     */
    public function assign($roles)
    {
        (new AssignsRoles($roles))->to($this);

        return $this;
    }

    /**
     * Retract the given role from the model.
     *
     * @param string|int|array $roles
     * @return $this
     */
    public function retract($roles)
    {
        (new RemovesRoles($roles))->from($this);

        return $this;
    }

    /**
     * Check if the model has any of the given roles.
     *
     * @param string $role
     * @return bool
     */
    public function is($role)
    {
        $roles = func_get_args();
        $clipboard = Helper::clipboard();

        return $clipboard->checkRole($this, $roles, 'or');
    }

    /**
     * Check if the model has none of the given roles.
     *
     * @param string $role
     * @return bool
     */
    public function isNot($role)
    {
        $roles = func_get_args();
        $clipboard = Helper::clipboard();

        return $clipboard->checkRole($this, $roles, 'not');
    }

    /**
     * Check if the model has all of the given roles.
     *
     * @param string $role
     * @return bool
     */
    public function isAll($role)
    {
        $roles = func_get_args();
        $clipboard = Helper::clipboard();

        return $clipboard->checkRole($this, $roles, 'and');
    }

    /**
     * Constrain the given query by the provided roles.
     *
     * @param $query
     * @param string|string[] $role
     * @return mixed
     */
    public function scopeWhereIs($query, $role)
    {
        $roles = is_array($role) ? $role : array_slice(func_get_args(), 1);

        $this->scopeJoinRoles($query);

        return $query->whereIn(Helper::rolesTable() . '.name', $roles);
    }

    /**
     * Join roles to the query.
     *
     * @param $query
     * @return mixed
     */
    public function scopeJoinRoles($query)
    {
        $assignedRolesTable = Helper::assignedRolesTable();
        $rolesTable = Helper::rolesTable();

        return $query
            ->leftJoin($assignedRolesTable, "{$assignedRolesTable}.entity_id", '=', "{$this->getTable()}.{$this->getKeyName()}")
            ->where("{$assignedRolesTable}.entity_type", $this->getMorphClass())
            ->leftJoin($rolesTable, "{$assignedRolesTable}.role_id", '=', "{$rolesTable}.id")
            ->select(["{$this->getTable()}.*"]);
    }

    /**
     * Constrain the given query by the provided roles.
     *
     * @param $query
     * @param string|string[] $role
     * @return mixed
     */
    public function scopeWhereIsAll($query, $role)
    {
        $roles = is_array($role) ? $role : array_slice(func_get_args(), 1);

        $this->scopeJoinRoles($query);
        $rolesTable = Helper::rolesTable();

        foreach ($roles as $name) {
            $query->where("{$rolesTable}.name", $name);
        }

        return $query;
    }

    /**
     * Constrain the given query by the provided roles.
     *
     * @param $query
     * @param string|string[] $role
     * @return mixed
     */
    public function scopeWhereIsNot($query, $role)
    {
        $roles = is_array($role) ? $role : array_slice(func_get_args(), 1);

        $this->scopeJoinRoles($query);
        $rolesTable = Helper::rolesTable();

        return $query
            ->whereNotIn("{$rolesTable}.name", $roles)
            ->orWhereNull("{$rolesTable}.name"); // includes models that have no roles
    }


}