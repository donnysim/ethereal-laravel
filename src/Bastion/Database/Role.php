<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Exceptions\InvalidAuthorityException;
use Ethereal\Bastion\Exceptions\InvalidRoleException;
use Ethereal\Bastion\Helper;
use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Role extends Ethereal
{
    protected $casts = [
        'id' => 'int',
        'system' => 'bool',
        'private' => 'bool',
        'level' => 'int',
    ];

    protected $columns = ['id', 'name', 'title', 'system', 'private', 'level', 'created_at', 'updated_at'];

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
     * Ensure all roles are valid.
     *
     * @param array $roles
     *
     * @return \Illuminate\Support\Collection
     * @throws \Ethereal\Bastion\Exceptions\InvalidRoleException
     */
    public static function ensureRoles(array $roles): Collection
    {
        $roleList = new Collection();

        $findRoles = [];
        foreach ($roles as $key => $role) {
            if (\is_string($role)) {
                $findRoles[] = $role;
            } elseif ($role instanceof Model) {
                if (!$role->exists) {
                    throw new InvalidRoleException("Role `{$role->name}` is not persisted in database.");
                }

                $roleList->push($role);
            }
        }

        if (\count($findRoles)) {
            $roles = static::whereIn('name', $findRoles)->get(['id', 'name']);
            $missing = array_values(array_diff($findRoles, $roles->pluck('name')->all()));

            if (\count($missing)) {
                throw new InvalidRoleException("Role `{$missing[0]}` does not exists.");
            }

            $roleList = $roleList->merge($roles);
        }

        return $roleList->keyBy((new static)->getKeyName());
    }

    /**
     * Get all authority roles.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public static function allRoles(Model $authority)
    {
        return self::authorityQuery($authority)->get();
    }

    /**
     * Start authority role query.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    protected static function authorityQuery(Model $authority): Builder
    {
        if (!$authority->exists) {
            throw new InvalidAuthorityException('Authority must exist to retrieve assigned roles.');
        }

        $role = new static();
        return $role
            ->newQuery()
            ->whereIn($role->getKeyName(), function ($query) use ($authority, $role) {
                $query
                    ->select($role->getForeignKey())
                    ->from(Helper::getAssignedRolesTable())
                    ->where([
                        'model_id' => $authority->getKey(),
                        'model_type' => $authority->getMorphClass(),
                    ]);
            });
    }

    /**
     * Assign role to authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return $this|\Illuminate\Database\Eloquent\Model
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidRoleException
     */
    public function assignTo(Model $authority)
    {
        if (!$authority->exists) {
            throw new InvalidAuthorityException('Authority must exist to assign a role.');
        }

        if (!$this->exists) {
            throw new InvalidRoleException('Role must be saved before it can be assigned to authority.');
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
