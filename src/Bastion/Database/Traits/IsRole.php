<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

trait IsRole
{
    /**
     * Get roles assigned to authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Illuminate\Support\Collection
     * @throws \InvalidArgumentException
     */
    public static function getRoles(Model $authority)
    {
        if (!$authority->exists) {
            throw new InvalidArgumentException('Authority must exist to retrieve assigned roles.');
        }

        $role = new static;
        $query = $role->newQuery();

        return $query->whereIn($role->getKeyName(), function ($query) use ($role, $authority) {
            $query
                ->select('role_id')
                ->from(Helper::getAssignedRoleTable())
                ->where('entity_id', $authority->getKey())
                ->where('entity_type', $authority->getMorphClass());
        })->get()->keyBy('id');
    }

    /**
     * Get or create roles based on provided list.
     *
     * @param array $roles
     *
     * @return Collection
     * @throws \InvalidArgumentException
     */
    public static function collectRoles($roles)
    {
        $rolesList = collect([]);

        foreach ($roles as $role) {
            if ($role instanceof Model) {
                if (!$role->exists) {
                    throw new InvalidArgumentException('Provided role model does not existing. Did you forget to save it?');
                }

                $rolesList->push($role);
            } elseif (is_numeric($role)) {
                $rolesList->push(static::findOrFail($role));
            } elseif (is_string($role)) {
                $rolesList->push(static::firstOrCreate([
                    'name' => $role,
                ]));
            } elseif (is_array($role)) {
                $rolesList->push(static::forceCreate($role));
            }
        }

        return $rolesList->keyBy('id');
    }

    /**
     * Get role level.
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->attributes['level'];
    }

    /**
     * Create assign role record.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return array
     */
    public function createAssignRecord(Model $authority)
    {
        return [
            'role_id' => $this->getKey(),
            'entity_id' => $authority->getKey(),
            'entity_type' => $authority->getMorphClass(),
        ];
    }
}
