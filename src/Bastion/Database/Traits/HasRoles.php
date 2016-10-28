<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Helper;

/**
 * @mixin \Ethereal\Database\Ethereal
 */
trait HasRoles
{
    /**
     * The roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function roles()
    {
        return $this->morphToMany(Helper::getRoleModelClass(), 'entity', Helper::getRoleTable(), 'entity_id', 'role_id');
    }

    /**
     * Assign the given roles to the model.
     *
     * @param string|int|array $roles
     *
     * @return $this
     */
    public function assign($roles)
    {
        Helper::bastion()->assign($roles)->to($this);

        return $this;
    }

    /**
     * Retract the given role from the model.
     *
     * @param string|int|array $roles
     *
     * @return $this
     */
    public function retract($roles)
    {
        Helper::bastion()->retract($roles)->from($this);

        return $this;
    }

    /**
     * Check if the model has any of the given roles.
     *
     * @param string|array $role
     *
     * @return bool
     */
    public function isA($role)
    {
        return Helper::bastion()->is($this)->a(is_array($role) ? $role : func_get_args());
    }

    /**
     * Check if the model has none of the given roles.
     *
     * @param string|array $role
     *
     * @return bool
     */
    public function isNot($role)
    {
        return Helper::bastion()->is($this)->notA(is_array($role) ? $role : func_get_args());
    }

    /**
     * Check if the model has all of the given roles.
     *
     * @param string|array $role
     *
     * @return bool
     */
    public function isAll($role)
    {
        return Helper::bastion()->is($this)->all(is_array($role) ? $role : func_get_args());
    }

    /**
     * Constrain the given query by the provided roles.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string|string[] $role
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function scopeWhereIs($query, $role)
    {
        $roles = is_array($role) ? $role : array_slice(func_get_args(), 1);

        $this->scopeJoinRoles($query);

        return $query->whereIn(Helper::getRoleTable() . '.name', $roles);
    }

    /**
     * Join roles to the query.
     *
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function scopeJoinRoles($query)
    {
        $assignedRolesTable = Helper::getAssignedRoleTable();
        $rolesTable = Helper::getRoleTable();

        return $query
            ->leftJoin($assignedRolesTable, "{$assignedRolesTable}.entity_id", '=', "{$this->getTable()}.{$this->getKeyName()}")
            ->where("{$assignedRolesTable}.entity_type", $this->getMorphClass())
            ->leftJoin($rolesTable, "{$assignedRolesTable}.role_id", '=', "{$rolesTable}.id")
            ->select(["{$this->getTable()}.*"]);
    }

    /**
     * Constrain the given query by the provided roles.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string|string[] $role
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function scopeWhereIsAll($query, $role)
    {
        $roles = is_array($role) ? $role : array_slice(func_get_args(), 1);

        $this->scopeJoinRoles($query);
        $rolesTable = Helper::getRoleTable();

        foreach ($roles as $name) {
            $query->where("{$rolesTable}.name", $name);
        }

        return $query;
    }

    /**
     * Constrain the given query by the provided roles.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string|string[] $role
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function scopeWhereIsNot($query, $role)
    {
        $roles = is_array($role) ? $role : array_slice(func_get_args(), 1);

        $this->scopeJoinRoles($query);
        $rolesTable = Helper::getRoleTable();

        return $query
            ->whereNotIn("{$rolesTable}.name", $roles)
            ->orWhereNull("{$rolesTable}.name"); // includes models that have no roles
    }
}
