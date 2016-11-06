<?php

namespace Ethereal\Bastion\Database\Contracts;

use Illuminate\Database\Eloquent\Model;

interface RoleContract
{
    /**
     * Get roles assigned to authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Illuminate\Support\Collection
     * @throws \InvalidArgumentException
     */
    public static function getRoles(Model $authority);

    /**
     * Get or create roles based on provided list.
     *
     * @param array $roles
     *
     * @return \Illuminate\Support\Collection
     * @throws \InvalidArgumentException
     */
    public static function collectRoles($roles);

    /**
     * Create assign role record.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return array
     */
    public function createAssignRecord(Model $authority);
}
