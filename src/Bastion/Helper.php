<?php

namespace Ethereal\Bastion;

use Ethereal\Bastion\Database\AssignedPermission;
use Ethereal\Bastion\Database\AssignedRole;
use Ethereal\Bastion\Database\Permission;
use Ethereal\Bastion\Database\Role;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class Helper
{
    /**
     * Get model class name.
     *
     * @return string
     */
    public static function getAssignedPermissionModelClass(): string
    {
        return static::getConfig('bastion.models.assigned_permission', AssignedPermission::class);
    }

    /**
     * Get table.
     *
     * @return string
     */
    public static function getAssignedPermissionsTable(): string
    {
        return static::getConfig('bastion.tables.assigned_permissions', 'assigned_permissions');
    }

    /**
     * Get model class name.
     *
     * @return string
     */
    public static function getAssignedRoleModelClass(): string
    {
        return static::getConfig('bastion.models.assigned_role', AssignedRole::class);
    }

    /**
     * Get table.
     *
     * @return string
     */
    public static function getAssignedRolesTable(): string
    {
        return static::getConfig('bastion.tables.assigned_roles', 'assigned_roles');
    }

    /**
     * Get model type and id.
     *
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param string|int|null $id
     *
     * @return array
     */
    public static function getModelTypeAndId($model, $id): array
    {
        $modelType = null;
        $modelId = null;

        if (\is_string($model)) {
            $modelType = static::getMorphOfClass($model);
            $modelId = $id;
            return [$modelType, $modelId];
        }

        if ($model instanceof Model) {
            $modelType = $model->getMorphClass();
            $modelId = $model->getKey();
            return [$modelType, $modelId];
        }

        return [$modelType, $modelId];
    }

    /**
     * Find class morph name from class path.
     *
     * @param string $classPath
     *
     * @return string
     */
    public static function getMorphOfClass($classPath): string
    {
        $morphMap = Relation::morphMap();

        foreach ($morphMap as $name => $class) {
            if ($class === $classPath) {
                return $name;
            }
        }

        return $classPath;
    }

    /**
     * Get model class name.
     *
     * @return string
     */
    public static function getPermissionModelClass(): string
    {
        return static::getConfig('bastion.models.permission', Permission::class);
    }

    /**
     * Get table.
     *
     * @return string
     */
    public static function getPermissionsTable(): string
    {
        return static::getConfig('bastion.tables.permissions', 'permissions');
    }

    /**
     * Get model class name.
     *
     * @return string
     */
    public static function getRoleModelClass(): string
    {
        return static::getConfig('bastion.models.role', Role::class);
    }

    /**
     * Get table.
     *
     * @return string
     */
    public static function getRolesTable(): string
    {
        return static::getConfig('bastion.tables.roles', 'roles');
    }

    /**
     * Get application config.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    protected static function getConfig($key, $default = null)
    {
        try {
            return \app('config')->get($key, $default);
        } catch (EntryNotFoundException $e) {
            return $default;
        }
    }
}
