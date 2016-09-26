<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Model;

class RemovesRoles
{
    /**
     * List of roles to remove from authority.
     *
     * @var array|string
     */
    protected $roles;

    /**
     * RemovesRoles constructor.
     *
     * @param string|int|array $roles
     */
    public function __construct($roles)
    {
        $this->roles = is_array($roles) ? $roles : func_get_args();
    }

    /**
     * Remove roles from provided authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Model[] $authority
     */
    public function from($authority)
    {
        $authorities = is_array($authority) ? $authority : func_get_args();
        $roles = Helper::collectRoles($this->roles)->pluck('id')->all();

        $assignedRolesTable = Helper::assignedRolesTable();
        $query = Helper::database()->table($assignedRolesTable);

        foreach ($authorities as $auth) {
            if (! $auth instanceof Model || ! $auth->exists) {
                throw new \InvalidArgumentException('Provided authority must be an existing model.');
            }

            $query->orWhere(function ($query) use ($assignedRolesTable, $auth, $roles) {
                /** @var \Illuminate\Database\Query\Builder $query */
                $query
                    ->whereIn("{$assignedRolesTable}.role_id", $roles)
                    ->where("{$assignedRolesTable}.entity_id", $auth->getKey())
                    ->where("{$assignedRolesTable}.entity_type", $auth->getMorphClass());
            });
        }

        $query->delete();
    }
}