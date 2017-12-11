<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Exceptions\InvalidAuthorityException;
use Ethereal\Bastion\Exceptions\InvalidPermissionException;
use Ethereal\Bastion\Helper;
use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property string name
 * @property string|null model_type
 * @property string|null model_id
 * @property string identifier
 */
class Permission extends Ethereal
{
    protected $casts = [
        'id' => 'int',
        'forbid' => 'bool',
    ];

    protected $columns = ['id', 'name', 'title', 'model_id', 'model_type', 'created_at', 'updated_at'];

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
     * Create a new permission.
     *
     * @param string $permission
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param string|int|null $id
     * @param array $attributes
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function createPermission($permission, $model = null, $id = null, array $attributes = []): Model
    {
        list($modelType, $modelId) = Helper::getModelTypeAndId($model, $id);

        return static::create(
            \array_merge([
                'name' => $permission,
                'model_id' => $modelId,
                'model_type' => $modelType,
            ], $attributes)
        );
    }

    /**
     * Ensure all permissions are valid.
     *
     * @param array $permissions
     * @param \Illuminate\Database\Eloquent\Model|null $model
     * @param int|null $id
     *
     * @return \Illuminate\Support\Collection
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     */
    public static function ensurePermissions($permissions, $model = null, $id = null): Collection
    {
        $permissionsList = new Collection();

        foreach ($permissions as $key => $permission) {
            if (\is_string($permission)) {
                $permissionsList->push(
                    static::findPermission($permission, $model, $id) ?:
                        static::createPermission($permission, $model, $id)
                );
            } elseif ($permission instanceof Model) {
                if (!$permission->exists) {
                    throw new InvalidPermissionException('Permission must be persisted to databse before assigning.');
                }

                $permissionsList->push($permission);
            }
        }

        return $permissionsList;
    }

    /**
     * Find permission.
     *
     * @param string $name
     * @param string|null $model
     * @param string|int|null $id
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public static function findPermission($name, $model = null, $id = null)
    {
        list($modelType, $modelId) = Helper::getModelTypeAndId($model, $id);

        return static::query()
            ->where([
                'name' => $name,
                'model_id' => $modelId,
                'model_type' => $modelType,
            ])
            ->first();
    }

    /**
     * Get authority permissions.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param \Illuminate\Support\Collection|null $roles
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    public static function ofAuthority(Model $authority, Collection $roles = null): EloquentCollection
    {
        if (!$authority->exists) {
            throw new InvalidAuthorityException('Authority must exist to retrieve assigned permissions.');
        }

        $permission = new static();
        $query = $permission->newQuery();
        $apTable = Helper::getAssignedPermissionsTable();

        $query
            ->join($apTable, 'permission_id', '=', "{$permission->getTable()}.id")
            ->where([
                "$apTable.model_id" => $authority->getKey(),
                "$apTable.model_type" => $authority->getMorphClass(),
            ]);

        if ($roles && $roles->count()) {
            $query->orWhere(function ($query) use ($apTable, $roles) {
                $model = $roles->first();
                $query->whereIn("$apTable.model_id", $roles->pluck($model->getKeyName()))
                    ->where("$apTable.model_type", $model->getMorphClass());
            });
        }

        return $query->get(["{$permission->getTable()}.*", "$apTable.forbid"]);
    }

    /**
     * Assign permission to authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param bool $forbid
     *
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     * @throws \Ethereal\Bastion\Exceptions\InvalidPermissionException
     */
    public function assignTo(Model $authority, $forbid = false)
    {
        if (!$authority->exists) {
            throw new InvalidAuthorityException('Authority must exist to assign a permission.');
        }

        if (!$this->exists) {
            throw new InvalidPermissionException('Permission must be saved before it can be assigned to authority.');
        }

        $assignClass = Helper::getAssignedPermissionModelClass();
        $assignClass::create([
            'permission_id' => $this->getKey(),
            'model_id' => $authority->getKey(),
            'model_type' => $authority->getMorphClass(),
            'forbid' => $forbid,
        ]);
    }

    /**
     * Get permission identifier.
     *
     * @return string
     */
    public function getIdentifierAttribute(): string
    {
        $identifier = [$this->name];

        if ($this->model_type) {
            $identifier[] = $this->model_type;
        }

        if ($this->model_id) {
            $identifier[] = $this->model_id;
        }

        return \implode('-', $identifier);
    }

    /**
     * Retract permission from authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param bool|null $forbid
     */
    public function removeFrom(Model $authority, $forbid = null)
    {
        $assignClass = Helper::getAssignedPermissionModelClass();
        $assignClass
            ::where([
                'permission_id' => $this->getKey(),
                'model_id' => $authority->getKey(),
                'model_type' => $authority->getMorphClass(),
            ])
            ->when($forbid !== null, function ($query) use ($forbid) {
                $query->where('forbid', $forbid);
            })->delete();
    }
}
