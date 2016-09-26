<?php

namespace Ethereal\Bastion;

use Ethereal\Bastion\Database\Ability;
use Ethereal\Bastion\Database\AssignedRole;
use Ethereal\Bastion\Database\Permission;
use Ethereal\Bastion\Database\Role;
use Illuminate\Database\Eloquent\Relations\Relation;

class Helper
{
    /**
     * Get application config.
     *
     * @param string $key
     * @param mixed $default
     * @return \Illuminate\Config\Repository
     */
    protected static function getConfig($key, $default = null)
    {
        return app('config')->get($key, $default);
    }

    /**
     * Get ability model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function getAbilityModel()
    {
        $class = static::getAbilityModelClass();

        return new $class;
    }

    /**
     * Get ability model class name.
     *
     * @return string
     */
    public static function getAbilityModelClass()
    {
        $class = static::getConfig('bastion.models.ability', Ability::class);

        return $class;
    }

    /**
     * Get abilities table.
     *
     * @return string
     */
    public static function getAbilityTable()
    {
        $class = static::getConfig('bastion.tables.abilities', 'abilities');

        return $class;
    }

    /**
     * Get assigned role model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function getAssignedRoleModel()
    {
        $class = static::getAssignedRoleModelClass();

        return new $class;
    }

    /**
     * Get assigned role model class name.
     *
     * @return string
     */
    public static function getAssignedRoleModelClass()
    {
        $class = static::getConfig('bastion.models.assigned_role', AssignedRole::class);

        return $class;
    }

    /**
     * Get assigned role table.
     *
     * @return string
     */
    public static function getAssignedRoleTable()
    {
        $class = static::getConfig('bastion.tables.assigned_roles', 'assigned_roles');

        return $class;
    }

    /**
     * Get role model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function getRoleModel()
    {
        $class = static::getRoleModelClass();

        return new $class;
    }

    /**
     * Get role model class name.
     *
     * @return string
     */
    public static function getRoleModelClass()
    {
        $class = static::getConfig('bastion.models.role', Role::class);

        return $class;
    }

    /**
     * Get role table.
     *
     * @return string
     */
    public static function getRoleTable()
    {
        $class = static::getConfig('bastion.tables.roles', 'roles');

        return $class;
    }

    /**
     * Get permission model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function getPermissionModel()
    {
        $class = static::getPermissionModelClass();

        return new $class;
    }

    /**
     * Get permission model class name.
     *
     * @return string
     */
    public static function getPermissionModelClass()
    {
        $class = static::getConfig('bastion.models.permission', Permission::class);

        return $class;
    }

    /**
     * Get permission table.
     *
     * @return string
     */
    public static function getPermissionTable()
    {
        $class = static::getConfig('bastion.tables.permissions', 'permissions');

        return $class;
    }

    /**
     * Find class morph name from class path.
     *
     * @param string $classPath
     * @return string
     */
    public static function getMorphClassName($classPath)
    {
        $morphMap = Relation::morphMap();

        foreach ($morphMap as $name => $class) {
            if ($class === $classPath) {
                return $name;
            }
        }

        return $classPath;
    }
}