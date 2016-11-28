<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @mixin \Ethereal\Database\Ethereal
 */
trait IsAbility
{
    /**
     * Compile a list of ability identifiers that match the provided parameters.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string $model
     *
     * @return array
     */
    public static function compileAbilityIdentifiers($ability, $model)
    {
        $ability = strtolower($ability);

        if ($model === null) {
            return [$ability, '*-*', '*'];
        }

        return static::compileModelAbilityIdentifiers($ability, $model);
    }

    /**
     * Compile a list of ability identifiers that match the given model.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string $model
     *
     * @return array
     */
    public static function compileModelAbilityIdentifiers($ability, $model)
    {
        if ($model === '*') {
            return ["{$ability}-*", '*-*'];
        }

        $model = $model instanceof Model ? $model : new $model;

        $type = strtolower($model->getMorphClass());

        $abilities = [
            "{$ability}-{$type}",
            "{$ability}-*",
            "*-{$type}",
            '*-*'
        ];

        if ($model->exists) {
            $abilities[] = "{$ability}-{$type}-{$model->getKey()}";
            $abilities[] = "*-{$type}-{$model->getKey()}";
        }

        return $abilities;
    }

    /**
     * Get abilities assigned to authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param \Illuminate\Database\Eloquent\Collection|null $roles
     *
     * @return array|\Illuminate\Database\Eloquent\Collection|static[]
     * @throws \InvalidArgumentException
     */
    public static function getAbilities(Model $authority, Collection $roles = null)
    {
        if (!$authority->exists) {
            throw new InvalidArgumentException('Authority must exist to retrieve abilities.');
        }

        $ability = new static;
        /** @var \Illuminate\Database\Query\Builder $query */
        $query = $ability->newQuery();

        $permissionTable = Helper::getPermissionTable();

        $query
            // Join permissions
            ->join($permissionTable, "{$permissionTable}.ability_id", '=', $ability->getQualifiedKeyName())
            // Apply authority constraints
            ->where(function ($query) use ($permissionTable, $authority) {
                /** @var \Illuminate\Database\Query\Builder $query */
                $query
                    ->where("{$permissionTable}.entity_id", $authority->getKey())
                    ->where("{$permissionTable}.entity_type", $authority->getMorphClass());
            });

        // Apply roles constraints
        if ($roles !== null && !$roles->isEmpty()) {
            $query->orWhere(function ($query) use ($permissionTable, $authority, $roles) {
                /** @var \Illuminate\Database\Query\Builder $query */
                $role = Helper::getRoleModel();

                $query
                    ->whereIn("{$permissionTable}.entity_id", $roles->pluck($role->getKeyName()))
                    ->where("{$permissionTable}.entity_type", $role->getMorphClass());
            });
        }

        return $query->get(['abilities.*', "{$permissionTable}.forbidden"]);
    }

    /**
     * Collect abilities for authority.
     *
     * @param $abilities
     * @param string|\Illuminate\Database\Eloquent\Model|null $model
     * @param bool $create
     *
     * @return \Illuminate\Support\Collection
     * @throws \InvalidArgumentException
     */
    public static function collectAbilities($abilities, $model = null, $create = true)
    {
        $abilitiesList = collect([]);

        foreach ($abilities as $ability) {
            if ($ability instanceof Model) {
                if (!$ability->exists) {
                    throw new InvalidArgumentException('Provided ability model does not existing. Did you forget to save it?');
                }

                $abilitiesList->push($ability);
            } elseif (is_numeric($ability)) {
                $abilitiesList->push(static::findOrFail($ability));
            } elseif (is_string($ability)) {
                $entityType = null;
                if (is_string($model)) {
                    $entityType = Helper::getMorphClassName($model);
                } elseif ($model instanceof Model) {
                    $entityType = $model->getMorphClass();
                }

                $instance = static::query()
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
     * Create a new ability.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     *
     * @return mixed
     */
    public static function createAbility($ability, $model = null)
    {
        if ($model === null) {
            return static::forceCreate([
                'name' => $ability,
            ]);
        }

        if ($model === '*') {
            return static::forceCreate([
                'name' => $ability,
                'entity_type' => '*',
            ]);
        }

        return static::forceCreate([
            'name' => $ability,
            'entity_id' => $model instanceof Model && $model->exists ? $model->getKey() : null,
            'entity_type' => is_string($model) ? Helper::getMorphClassName($model) : $model->getMorphClass(),
        ]);
    }

    /**
     * Get status if the ability forbids permission.
     *
     * @return bool
     */
    public function isForbidden()
    {
        return (bool)$this->attributes['forbidden'];
    }

    /**
     * Get the ability's "slug" attribute.
     *
     * @return string
     */
    public function getSlugAttribute()
    {
        return $this->getIdentifierAttribute();
    }

    /**
     * Get the identifier for this ability.
     *
     * @return string
     */
    final public function getIdentifierAttribute()
    {
        $slug = $this->attributes['name'];

        if ($this->attributes['entity_type']) {
            $slug .= "-{$this->attributes['entity_type']}";
        }

        if ($this->attributes['entity_id']) {
            $slug .= "-{$this->attributes['entity_id']}";
        }

        return strtolower($slug);
    }
}
