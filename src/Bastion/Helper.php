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
        return static::getConfig('bastion.models.ability', Ability::class);
    }

    /**
     * Get application config.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return \Illuminate\Config\Repository
     */
    protected static function getConfig($key, $default = null)
    {
        return app('config')->get($key, $default);
    }

    /**
     * Get abilities table.
     *
     * @return string
     */
    public static function getAbilityTable()
    {
        return static::getConfig('bastion.tables.abilities', 'abilities');
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
        return static::getConfig('bastion.models.assigned_role', AssignedRole::class);
    }

    /**
     * Get assigned role table.
     *
     * @return string
     */
    public static function getAssignedRoleTable()
    {
        return static::getConfig('bastion.tables.assigned_roles', 'assigned_roles');
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
        return static::getConfig('bastion.models.role', Role::class);
    }

    /**
     * Get role table.
     *
     * @return string
     */
    public static function getRoleTable()
    {
        return static::getConfig('bastion.tables.roles', 'roles');
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
        return static::getConfig('bastion.models.permission', Permission::class);
    }

    /**
     * Get permission table.
     *
     * @return string
     */
    public static function getPermissionTable()
    {
        return static::getConfig('bastion.tables.permissions', 'permissions');
    }

    /**
     * Get bastion.
     *
     * @return \Ethereal\Bastion\Bastion
     */
    public static function bastion()
    {
        return app('bastion');
    }

    /**
     * Find class morph name from class path.
     *
     * @param string $classPath
     *
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
