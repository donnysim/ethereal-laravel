<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

trait IsAbility
{
    /**
     * Get ability identifier.
     *
     * @return string
     */
    public function getIdentifierAttribute()
    {
        $slug = $this->attributes['name'];

        if ($this->attributes['entity_type']) {
            $slug .= "-{$this->attributes['entity_type']}";
        } else {
            $slug .= '-*';
        }

        if ($this->attributes['entity_id']) {
            $slug .= "-{$this->attributes['entity_id']}";
        } else {
            $slug .= '-*';
        }

        // These attributes are joined when retrieved through bastion.
        if (isset($this->attributes['group'])) {
            $slug .= "-{$this->attributes['group']}";
        }

        if (isset($this->attributes['parent_id'])) {
            $slug .= "-{$this->attributes['parent_type']}-{$this->attributes['parent_id']}";
        }

        return strtolower($slug);
    }

    /**
     * Join permissions table.
     *
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return \Ethereal\Bastion\Database\Ability|\Illuminate\Database\Query\Builder
     */
    public function scopeJoinPermissions($query)
    {
        $permissionTable = Helper::getPermissionTable();

        return $query->join($permissionTable, "{$permissionTable}.ability_id", '=', (new static)->getQualifiedKeyName());
    }

    /**
     * Apply permission authority constraint.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param string $boolean
     *
     * @return \Ethereal\Bastion\Database\Ability|\Illuminate\Database\Query\Builder
     * @throws \InvalidArgumentException
     */
    public function scopeForAuthority($query, Model $authority, $boolean = 'and')
    {
        return $query->where(function ($query) use ($authority) {
            /** @var \Illuminate\Database\Query\Builder $query */
            $permissionTable = Helper::getPermissionTable();

            $query
                ->where("{$permissionTable}.target_id", $authority->getKey())
                ->where("{$permissionTable}.target_type", $authority->getMorphClass());
        }, null, null, $boolean);
    }

    /**
     * Apply roles constraint.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param \Illuminate\Support\Collection $roles
     * @param string $boolean
     *
     * @return \Ethereal\Bastion\Database\Ability|\Illuminate\Database\Query\Builder
     * @throws \InvalidArgumentException
     */
    public function scopeOfRoles($query, Collection $roles = null, $boolean = 'and')
    {
        if (!$roles || $roles->isEmpty()) {
            return $query;
        }

        return $query->where(function ($query) use ($roles) {
            /** @var \Illuminate\Database\Query\Builder $query */
            $permissionTable = Helper::getPermissionTable();
            $role = Helper::getRoleModel();

            $query
                ->whereIn("{$permissionTable}.target_id", $roles->pluck($role->getKeyName()))
                ->where("{$permissionTable}.target_type", $role->getMorphClass());
        }, null, null, $boolean);
    }

    /**
     * Compile possible ability identifiers.
     *
     * The minimum compiled format is the following:
     * {ability|*}-{morph|*}-{id|*}
     *
     * After the minimum compiled format a group and/or parent is added.
     * If group is specified -{group} is added;
     * If parent is specified -{parentMorph}-{parentId} is added.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|null $model
     * @param string|null $group
     * @param \Illuminate\Database\Eloquent\Model|null $parent
     *
     * @return array
     */
    public static function compileAbilityIdentifiers($ability, $model = null, $group = null, Model $parent = null)
    {
        $ability = strtolower($ability);

        $identifiers = [$ability, '*-*-*', '*'];
        if ($model) {
            $identifiers = static::compileModelAbilityIdentifiers($ability, $model);
        }

        foreach ($identifiers as &$identifier) {
            if ($group) {
                $identifier .= strtolower("-{$group}");
            }

            if ($parent) {
                $identifier .= strtolower("-{$parent->getMorphClass()}-{$parent->getKey()}");
            }
        }

        return $identifiers;
    }

    /**
     * Compile possible ability identifiers for model.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     *
     * @return array
     */
    public static function compileModelAbilityIdentifiers($ability, $model)
    {
        if ($model === '*') {
            return ["{$ability}-*", '*-*'];
        }

        $model = $model instanceof Model ? $model : new $model;
        $morph = strtolower($model->getMorphClass());

        $abilities = [
            "{$ability}-{$morph}-*",
            "{$ability}-*-*",
            "*-{$morph}-*",
            '*-*-*',
        ];

        if ($model->getKey()) {
            $abilities[] = "{$ability}-{$morph}-{$model->getKey()}";
            $abilities[] = "*-{$morph}-{$model->getKey()}";
        }

        return $abilities;
    }

    /**
     * Get all abilities of authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param \Illuminate\Support\Collection $roles
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \InvalidArgumentException
     */
    public static function getAbilities(Model $authority, Collection $roles = null)
    {
        if (!$authority->exists) {
            throw new InvalidArgumentException('Authority must exist to retrieve abilities.');
        }

        $ability = new static;
        /** @var \Illuminate\Database\Query\Builder|static $query */
        $query = $ability->newQuery();
        $permissionTable = Helper::getPermissionTable();

        return $query
            ->joinPermissions()
            ->forAuthority($authority)
            ->ofRoles($roles, 'or')
            ->get(['abilities.*', "{$permissionTable}.forbidden", "{$permissionTable}.group", "{$permissionTable}.parent_id", "{$permissionTable}.parent_type"]);
    }

    /**
     * Find ability by name.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param string|int|null $id
     *
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public static function findAbility($ability, $model = null, $id = null)
    {
        list($modelType, $modelId) = static::getModelTypeAndId($model, $id);

        return static::query()
            ->where('name', $ability)
            ->where('entity_id', $modelId)
            ->where('entity_type', $modelType)
            ->first();
    }

    /**
     * Create a new ability.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param string|int|null $id
     * @param array $attributes
     *
     * @return mixed
     */
    public static function createAbilityRecord($ability, $model = null, $id = null, array $attributes = [])
    {
        list($modelType, $modelId) = static::getModelTypeAndId($model, $id);

        return static::create(
            array_merge([
                'name' => $ability,
                'entity_id' => $modelId,
                'entity_type' => $modelType,
            ], $attributes)
        );
    }

    /**
     * Collection abilities.
     *
     * @param array $abilities
     * @param \Illuminate\Database\Eloquent\Model|null $model
     *
     * @return \Illuminate\Support\Collection
     */
    public static function collectAbilities($abilities, $model = null)
    {
        $abilitiesList = new Collection();

        foreach ($abilities as $key => $ability) {
            if ($ability instanceof Model) {
                if (!$ability->exists) {
                    $ability->save();
                }

                $abilitiesList->push($ability);
            } elseif (is_numeric($ability)) {
                $abilitiesList->push(static::findOrFail($ability));
            } elseif (is_string($key) && is_array($ability)) {
                $abilitiesList->push(
                    static::findAbility($key, $model) ?: static::createAbilityRecord($key, $model, null, $ability)
                );
            } elseif (is_string($ability)) {
                $abilitiesList->push(
                    static::findAbility($ability, $model) ?: static::createAbilityRecord($ability, $model)
                );
            }
        }

        return $abilitiesList->keyBy((new static)->getKeyName());
    }

    /**
     * Get model type and id.
     *
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param string|int|null $id
     *
     * @return array
     */
    protected static function getModelTypeAndId($model, $id)
    {
        $modelType = null;
        $modelId = null;

        if (is_string($model)) {
            $modelType = Helper::getMorphOfClass($model);
            $modelId = $id;
            return [$modelType, $modelId];
        } elseif ($model instanceof Model) {
            $modelType = $model->getMorphClass();
            $modelId = $model->getKey();
            return [$modelType, $modelId];
        }

        return [$modelType, $modelId];
    }
}
