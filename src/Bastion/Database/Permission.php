<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Helper;
use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Permission extends Ethereal
{
    protected $columns = ['id', 'name', 'title', 'guard', 'model_id', 'model_type', 'created_at', 'updated_at'];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = Helper::getPermissionsTable();

        parent::__construct($attributes);
    }

    /**
     * Build identifier.
     *
     * @param string $permission
     * @param string|\Illuminate\Database\Eloquent\Model $model
     *
     * @return string
     */
    public static function buildIdentifier($permission, $model)
    {
        $identifier = [\strtolower($permission)];

        if ($model) {
            if ($model instanceof Model) {
                $identifier[] = Str::slug(\strtolower($model->getMorphClass()));

                if ($model->getKey()) {
                    $identifier[] = $model->getKey();
                }
            } elseif (\is_string($model)) {
                $identifier[] = Str::slug(\strtolower(Helper::getMorphOfClass($model)));
            }
        }

        return \implode('-', $identifier);
    }

    /**
     * Create a new permission.
     *
     * @param string $permission
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param string|int|null $id
     * @param string $guard
     * @param array $attributes
     *
     * @return mixed
     */
    public static function createPermission($permission, $guard, $model = null, $id = null, array $attributes = [])
    {
        list($modelType, $modelId) = Helper::getModelTypeAndId($model, $id);

        return static::create(
            \array_merge([
                'name' => $permission,
                'guard' => $guard,
                'model_id' => $modelId,
                'model_type' => $modelType,
            ], $attributes)
        );
    }

    /**
     * Collection abilities.
     *
     * @param array $permissions
     * @param string $guard
     * @param \Illuminate\Database\Eloquent\Model|null $model
     * @param int|null $id
     *
     * @return \Illuminate\Support\Collection
     */
    public static function ensurePermissions($permissions, $guard, $model = null, $id = null)
    {
        $permissionsList = new Collection();

        foreach ($permissions as $key => $permission) {
            if ($permission instanceof Model) {
                if (!$permission->exists) {
                    $permission->save();
                }

                $permissionsList->push($permission);
            } elseif (\is_numeric($permission)) {
                $permissionsList->push(static::findOrFail($permission));
            } elseif (\is_string($key) && \is_array($permission)) {
                $permissionsList->push(
                    static::findPermission($key, $model, $guard)
                        ?: static::createPermission($key, $guard, $model, $id, $permission)
                );
            } elseif (\is_string($permission)) {
                $permissionsList->push(
                    static::findPermission($permission, $model, $id, $guard)
                        ?: static::createPermission($permission, $guard, $model, $id)
                );
            }
        }

        return $permissionsList->keyBy((new static)->getKeyName());
    }

    /**
     * Find ability by name.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param string|int|null $id
     * @param string|null $guard
     *
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public static function findPermission($ability, $model = null, $id = null, $guard = null)
    {
        list($modelType, $modelId) = Helper::getModelTypeAndId($model, $id);

        return static::query()
            ->when($guard, function ($query) use ($guard) {
                $query->where('guard', $guard);
            })
            ->where([
                'name' => $ability,
                'model_id' => $modelId,
                'model_type' => $modelType,
            ])
            ->first();
    }

    /**
     * Get authority roles.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param \Illuminate\Support\Collection|null $roles
     * @param string|null $guard
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function ofAuthority(Model $authority, Collection $roles = null, $guard = null)
    {
        if (!$authority->exists) {
            throw new InvalidArgumentException('Authority must exist to retrieve assigned permissions.');
        }

        $permission = new static();
        $query = $permission->newQueryWithoutScopes();

        return $query->whereIn($permission->getKeyName(), function ($query) use ($roles, $permission, $guard, $authority) {
            $query
                ->select($permission->getForeignKey() . ' AS `key`')
                ->from(Helper::getAssignedPermissionsTable())
                ->when($guard, function ($query) use ($guard) {
                    $query->where('guard', $guard);
                })
                ->where(function ($query) use ($authority, $roles) {
                    $query->where([
                        'model_id' => $authority->getKey(),
                        'model_type' => $authority->getMorphClass(),
                    ]);

                    if ($roles && $roles->count()) {
                        $query->orWhere(function ($query) use ($roles) {
                            $model = $roles->first();

                            $query->whereIn('model_id', $roles->pluck($model->getKeyName()))
                                ->where('model_type', $model->getMorphClass());
                        });
                    }
                });
        })->get()->keyBy($permission->getKeyName());
    }

    /**
     * Restrict query.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Support\Collection $data
     * @param string|null $guard
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected static function restrictTo($query, $data, $guard = null)
    {
        $query->when($guard, function ($query) use ($guard) {
            $query->where('guard', $guard);
        });

        if ($data instanceof Model) {
            $query
                ->where('model_id', $data->getKey())
                ->where('model_type', $data->getMorphClass());
        } elseif ($data instanceof Collection && $data->count()) {
            $model = $data->first();

            $query
                ->whereIn('model_id', $data->pluck($model->getKeyName()))
                ->where('model_type', $model->getMorphClass());
        }

        return $query;
    }

    /**
     * Assign permission to authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param string $guard
     */
    public function assignTo(Model $authority, $guard)
    {
        if (!$guard) {
            throw new InvalidArgumentException('Guard must be provided for permission assign.');
        }

        if (!$authority->exists) {
            throw new InvalidArgumentException('Authority must exist to assign a permission.');
        }

        if (!$this->exists) {
            throw new InvalidArgumentException('Permission must be saved before it can be assigned to authority.');
        }

        $assignClass = Helper::getAssignedPermissionModelClass();
        $assignClass::create([
            'permission_id' => $this->getKey(),
            'model_id' => $authority->getKey(),
            'model_type' => $authority->getMorphClass(),
            'guard' => $guard,
        ]);
    }

    /**
     * Compile permission identifier.
     *
     * @return string
     */
    public function compileIdentifier()
    {
        $identifier = [\strtolower($this->name)];

        if ($this->model_type) {
            $identifier[] = \strtolower($this->model_type);
        }

        if ($this->model_id) {
            $identifier[] = $this->model_id;
        }

        return \implode('-', $identifier);
    }
}
