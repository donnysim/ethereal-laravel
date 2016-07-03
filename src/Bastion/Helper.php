<?php

namespace Ethereal\Bastion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class Helper
{
    /**
     * Get assigned roles table.
     *
     * @return string
     */
    public static function assignedRolesTable()
    {
        return static::getConfig('bastion.tables.assigned_roles', 'assigned_roles');
    }

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
     * Get abilities table.
     *
     * @return string
     */
    public static function abilitiesTable()
    {
        return static::getConfig('bastion.tables.abilities', 'abilities');
    }

    /**
     * Get ability model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function abilityModel()
    {
        $class = static::abilityModelClass();

        return new $class;
    }

    /**
     * Get ability model class name.
     *
     * @return string
     */
    public static function abilityModelClass()
    {
        $class = static::getConfig('bastion.models.ability');

        return $class;
    }

    /**
     * Get permissions table.
     *
     * @return string
     */
    public static function permissionsTable()
    {
        return static::getConfig('bastion.tables.permissions', 'permissions');
    }

    /**
     * Get roles model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function rolesModel()
    {
        $class = static::rolesModelClass();

        return new $class;
    }

    /**
     * Get roles model class name.
     *
     * @return string
     */
    public static function rolesModelClass()
    {
        $class = static::getConfig('bastion.models.role');

        return $class;
    }

    /**
     * Get roles table.
     *
     * @return string
     */
    public static function rolesTable()
    {
        return static::getConfig('bastion.tables.roles', 'roles');
    }

    /**
     * Get database manager.
     *
     * @return \Illuminate\Database\DatabaseManager|\Illuminate\Database\Connection
     */
    public static function database()
    {
        return app('db');
    }

    /**
     * Get clipboard instance.
     *
     * @return \Ethereal\Bastion\Clipboard
     */
    public static function clipboard()
    {
        return app(Clipboard::class);
    }

    /**
     * Gather a collection of roles.
     *
     * @param string|int|array $roles
     * @return \Illuminate\Support\Collection
     */
    public static function collectRoles($roles)
    {
        $rolesList = collect([]);
        $rolesModelClass = Helper::rolesModelClass();

        foreach ($roles as $role) {
            if ($role instanceof Model) {
                if (! $role->exists) {
                    throw new \InvalidArgumentException('Provided role model does not existing. Did you forget to save it?');
                }

                $rolesList[] = $role;
            } elseif (is_numeric($role)) {
                $rolesList[] = $rolesModelClass::findOrFail($role);
            } elseif (is_string($role)) {
                $rolesList[] = $rolesModelClass::firstOrCreate([
                    'name' => $role,
                ]);
            }
        }

        return $rolesList;
    }

    /**
     * Gather a collection of abilities.
     *
     * @param $abilities
     * @param null $model
     * @param bool $create
     * @return \Illuminate\Support\Collection
     */
    public static function collectAbilities($abilities, $model = null, $create = true)
    {
        $abilitiesList = collect([]);
        $abilityModelClass = Helper::abilityModelClass();

        foreach ($abilities as $ability) {
            if ($ability instanceof Model) {
                if (! $ability->exists) {
                    throw new \InvalidArgumentException('Provided ability model does not existing. Did you forget to save it?');
                }

                $abilitiesList[] = $ability;
            } elseif (is_numeric($ability)) {
                $abilitiesList[] = $abilityModelClass::findOrFail($ability);
            } elseif (is_string($ability)) {
                $entityType = null;
                if (is_string($model)) {
                    $entityType = static::getMorphClassName($model);
                } elseif ($model instanceof Model) {
                    $entityType = $model->getMorphClass();
                }

                $instance = $abilityModelClass::query()
                    ->where('name', $ability)
                    ->where('entity_id', $model instanceof Model && $model->exists ? $model->getKey() : null)
                    ->where('entity_type', $entityType)
                    ->first();

                if ($instance) {
                    $abilitiesList[] = $instance;
                } elseif ($create) {
                    $abilitiesList[] = static::createAbility($ability, $model);
                }
            }
        }

        return $abilitiesList;
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

    /**
     * Create a new ability.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @return mixed
     */
    public static function createAbility($ability, $model = null)
    {
        $abilityClass = static::abilityModelClass();

        if ($model === null) {
            return $abilityClass::forceCreate([
                'name' => $ability,
            ]);
        }

        if ($model === '*') {
            return $abilityClass::forceCreate([
                'name' => $ability,
                'entity_type' => '*',
            ]);
        }

        return $abilityClass::forceCreate([
            'name' => $ability,
            'entity_id' => $model instanceof Model && $model->exists ? $model->getKey() : null,
            'entity_type' => is_string($model) ? static::getMorphClassName($model) : $model->getMorphClass(),
        ]);
    }

    /**
     * Get a list of authority classes.
     *
     * @return string[]
     */
    public static function authorities()
    {
        return static::getConfig('bastion.authorities', []);
    }
}