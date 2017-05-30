<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Database\AssignedRole;
use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

trait IsRole
{
    /**
     * Get all roles assigned to authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \InvalidArgumentException
     */
    public static function getRoles(Model $authority)
    {
        if (!$authority->exists) {
            throw new InvalidArgumentException('Authority must exist to retrieve assigned roles.');
        }

        /** @var \Ethereal\Bastion\Database\Role $role */
        $role = new static;
        $query = $role->newQueryWithoutScopes();

        return $query->whereIn($role->getKeyName(), function ($query) use ($authority) {
            $query
                ->select('role_id')
                ->from(Helper::getAssignedRoleTable())
                ->where('target_id', $authority->getKey())
                ->where('target_type', $authority->getMorphClass());
        })->get()->keyBy('id');
    }

    /**
     * Collect various roles from a list.
     *
     * @param array $roles
     *
     * @return \Illuminate\Support\Collection
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function collectRoles(array $roles)
    {
        $rolesList = new Collection();

        foreach ($roles as $key => $role) {
            if ($role instanceof Model) {
                if (!$role->exists) {
                    $role->save();
                }

                $rolesList->push($role);
            } elseif (is_string($role)) {
                $rolesList->push(static::firstOrCreate([
                    'name' => $role,
                ]));
            } elseif (is_string($key) && is_array($role)) {
                $model = static::firstOrNew(['name' => $key]);
                $model->fill($role)->save();

                $rolesList->push($model);
            } elseif (is_numeric($role)) {
                $rolesList->push(static::findOrFail($role));
            }
        }

        return $rolesList->keyBy((new static)->getKeyName());
    }

    /**
     * Create role assign record.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param array $attributes
     *
     * @return \Ethereal\Bastion\Database\AssignedRole|\Ethereal\Bastion\Database\Traits\IsRole
     * @throws \InvalidArgumentException
     */
    public function createAssignRecord(Model $authority, $attributes = [])
    {
        if (!$authority->exists) {
            throw new InvalidArgumentException('Authority must exist to assign a role.');
        }

        /** @var AssignedRole $assignClass */
        $assignClass = Helper::getAssignedRoleModelClass();

        return $assignClass::create(
            array_merge([
                'role_id' => $this->getKey(),
                'target_id' => $authority->getKey(),
                'target_type' => $authority->getMorphClass(),
            ], $attributes)
        );
    }
}
