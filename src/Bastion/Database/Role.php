<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Helper;
use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Role extends Ethereal
{
    protected $columns = ['id', 'name', 'title', 'guard', 'system', 'private', 'level', 'created_at', 'updated_at'];

    protected $casts = [
        'id' => 'int',
        'system' => 'bool',
        'private' => 'bool',
        'level' => 'int',
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = Helper::getRolesTable();

        parent::__construct($attributes);
    }

    /**
     * Collect various roles from a list.
     *
     * @param array $roles
     * @param string $guard
     *
     * @return \Illuminate\Support\Collection
     */
    public static function ensureRoles(array $roles, $guard)
    {
        $rolesList = new Collection();

        foreach ($roles as $key => $role) {
            if ($role instanceof Model) {
                if (!$role->exists) {
                    $role->save();
                }

                $rolesList->push($role);
            } elseif (\is_string($role)) {
                $rolesList->push(static::firstOrCreate(['name' => $role, 'guard' => $guard]));
            } elseif (\is_string($key) && \is_array($role)) {
                $model = static::firstOrNew(['name' => $key, 'guard' => $guard]);
                $model->fill($role)->save();

                $rolesList->push($model);
            } elseif (\is_numeric($role)) {
                $rolesList->push(static::findOrFail($role));
            }
        }

        return $rolesList->keyBy((new static)->getKeyName());
    }

    /**
     * Get authority roles.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param string|null $guard
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function ofAuthority(Model $authority, $guard = null)
    {
        if (!$authority->exists) {
            throw new InvalidArgumentException('Authority must exist to retrieve assigned roles.');
        }

        $role = new static();
        $query = $role->newQueryWithoutScopes();

        return $query->whereIn($role->getKeyName(), function ($query) use ($role, $guard, $authority) {
            $query->select($role->getForeignKey())->from(Helper::getAssignedRolesTable())
                ->when($guard, function ($query) use ($guard) {
                    $query->where('guard', $guard);
                })
                ->where([
                    'model_id' => $authority->getKey(),
                    'model_type' => $authority->getMorphClass(),
                ]);
        })->get()->keyBy($role->getKeyName());
    }

    /**
     * Assign role to authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param string $guard
     *
     * @return $this|\Illuminate\Database\Eloquent\Model
     */
    public function assignTo(Model $authority, $guard)
    {
        if (!$guard) {
            throw new InvalidArgumentException('Guard must be provided for role assign.');
        }

        if (!$authority->exists) {
            throw new InvalidArgumentException('Authority must exist to assign a role.');
        }

        if (!$this->exists) {
            throw new InvalidArgumentException('Role must be saved before it can be assigned to authority.');
        }

        /** @var \Ethereal\Bastion\Database\AssignedRole $assignClass */
        $assignClass = Helper::getAssignedRoleModelClass();

        return $assignClass::create([
            'role_id' => $this->getKey(),
            'model_id' => $authority->getKey(),
            'model_type' => $authority->getMorphClass(),
        ]);
    }
}
