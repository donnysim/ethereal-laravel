<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * Assign roles to authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array $authority
     */
    public function to($authority)
    {
        $authorities = is_array($authority) ? $authority : func_get_args();
        $roles = Helper::collectRoles($this->roles)->keyBy('id');

        foreach ($authorities as $auth) {
            $existingRoles = $this->getExistingRoles($auth)->keyBy('id');
            $missingRoles = $roles->keys()->diff($existingRoles->keys());
            $inserts = [];

            foreach ($missingRoles as $missingRoleId) {
                $inserts[] = [
                    'role_id' => $missingRoleId,
                    'entity_id' => $auth->getKey(),
                    'entity_type' => $auth->getMorphClass(),
                ];
            }

            $this->executeInserts($inserts);
        }
    }

    /**
     * Get assigned roles for authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getExistingRoles(Model $authority)
    {
        if (method_exists($authority, 'roles')) {
            return $authority->roles;
        }

        $rolesModel = Helper::rolesModel();
        $assignedRolesTable = Helper::assignedRolesTable();

        return Helper::database()
            ->table(Helper::rolesTable())
            ->join($assignedRolesTable, "{$assignedRolesTable}.role_id", '=', "{$rolesModel->getTable()}.{$rolesModel->getKeyName()}")
            ->where("{$assignedRolesTable}.entity_id", $authority->getKey())
            ->where("{$assignedRolesTable}.entity_type", $authority->getMorphClass())
            ->get(["{$rolesModel->getTable()}.*"]);
    }

    /**
     * Execute roles insert query.
     *
     * @param $inserts
     */
    protected function executeInserts($inserts)
    {
        Helper::database()
            ->table(Helper::assignedRolesTable())
            ->insert($inserts);
    }
}