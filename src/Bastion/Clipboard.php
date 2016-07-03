<?php

namespace Ethereal\Bastion;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Database\Eloquent\Model;

class Clipboard
{
    /**
     * Whether the bastion is the exclusive authority on gate access.
     *
     * @var bool
     */
    protected $exclusive = false;

    /**
     * Register the clipboard at the given gate.
     *
     * @param \Illuminate\Contracts\Auth\Access\Gate $gate
     */
    public function registerAt(GateContract $gate)
    {
        $gate->before(function ($authority, $ability, $arguments = [], $additional = null) {

            list($model, $additional) = $this->parseGateArguments($arguments, $additional);

            if ($additional !== null) {
                return null;
            }

            if ($this->check($authority, $ability, $model)) {
                return true;
            }

            if ($this->exclusive) {
                return false;
            }

            return null;
        });
    }

    /**
     * Parse the arguments we got from the gate.
     *
     * @param mixed $arguments
     * @param mixed $additional
     * @return array
     */
    protected function parseGateArguments($arguments, $additional)
    {
        // The way arguments are passed into the gate's before callback has changed in Laravel
        // in the middle of the 5.2 release. Before, arguments were spread out. Now they're
        // all supplied in a single array instead. We will normalize it into two values.
        if ($additional !== null) {
            return [$arguments, $additional];
        }

        if (is_array($arguments)) {
            return [
                isset($arguments[0]) ? $arguments[0] : null,
                isset($arguments[1]) ? $arguments[1] : null,
            ];
        }

        return [$arguments, null];
    }

    /**
     * Determine if the given authority has the given ability.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @return bool
     */
    public function check(Model $authority, $ability, $model = null)
    {
        list($allowed, $forbidden) = $this->getAbilityMap($authority);
        $requested = $this->compileAbilityIdentifiers($ability, $model);

        $shouldBeAllowed = false;

        foreach ($requested as $ablt) {
            if (isset($forbidden[$ablt])) {
                return false;
            } elseif (! $shouldBeAllowed && isset($allowed[$ablt])) {
                $shouldBeAllowed = true;
            }
        }

        return $shouldBeAllowed;
    }

    /**
     * Get two arrays, first one holds allowed abilities, second holder forbidden
     * abilities.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @return array [$allowed, $forbidden]
     */
    protected function getAbilityMap(Model $authority)
    {
        $abilities = $this->getAbilities($authority);

        return [
            $abilities->filter(function ($value) {
                return ((bool)$value['forbidden']) === false;
            })->keyBy('identifier'),
            $abilities->filter(function ($value) {
                return ((bool)$value['forbidden']) === true;
            })->keyBy('identifier'),
        ];
    }

    /**
     * Get a list of the authority's abilities.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities(Model $authority)
    {
        $authorityMorph = $authority->getMorphClass();
        $authorityKey = $authority->getKey();

        $permissionsTable = Helper::permissionsTable();
        $abilitiesTable = Helper::abilitiesTable();
        $rolesMorph = Helper::rolesModel()->getMorphClass();

        return Helper::abilityModel()
            // Join permissions table
            ->join($permissionsTable, "{$permissionsTable}.ability_id", '=', "{$abilitiesTable}.id")
            // Apply authority constraints
            ->where(function ($query) use ($permissionsTable, $authorityMorph, $authorityKey) {
                $query->where("{$permissionsTable}.entity_id", $authorityKey)->where("{$permissionsTable}.entity_type", $authorityMorph);
            })
            // Apply roles constraints
            ->orWhere(function ($query) use ($permissionsTable, $authorityKey, $authorityMorph, $rolesMorph) {
                $query->whereIn("{$permissionsTable}.entity_id", function ($query) use ($permissionsTable, $authorityKey, $authorityMorph) {
                    $query
                        ->select('role_id')
                        ->from(Helper::assignedRolesTable())
                        ->where('entity_id', $authorityKey)
                        ->where('entity_type', $authorityMorph);
                })->where("{$permissionsTable}.entity_type", $rolesMorph);
            })->get(['abilities.*']);
    }

    /**
     * Compile a list of ability identifiers that match the provided parameters.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string $model
     * @return array
     */
    protected function compileAbilityIdentifiers($ability, $model)
    {
        $ability = strtolower($ability);

        if ($model === null) {
            return [$ability, '*-*', '*'];
        }

        return $this->compileModelAbilityIdentifiers($ability, $model);
    }

    /**
     * Compile a list of ability identifiers that match the given model.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string $model
     * @return array
     */
    protected function compileModelAbilityIdentifiers($ability, $model)
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
            '*-*',
        ];

        if ($model->exists) {
            $abilities[] = "{$ability}-{$type}-{$model->getKey()}";
            $abilities[] = "*-{$type}-{$model->getKey()}";
        }

        return $abilities;
    }

    /**
     * Set whether the bastion is the exclusive authority on gate access.
     *
     * @param bool $boolean
     * @return $this
     */
    public function setExclusivity($boolean)
    {
        $this->exclusive = $boolean;
    }

    /**
     * Check if an authority has the given roles.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param array|string $roles
     * @param string $boolean
     * @return bool
     */
    public function checkRole(Model $authority, $roles, $boolean = 'or')
    {
        $available = $this->getRoles($authority)->intersect($roles);

        if ($boolean === 'or') {
            return $available->count() > 0;
        } elseif ($boolean === 'not') {
            return $available->count() === 0;
        }

        return $available->count() === count((array)$roles);
    }

    /**
     * Get the given authority's roles.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @return \Illuminate\Support\Collection
     */
    public function getRoles(Model $authority)
    {
        return $authority->roles()->pluck('name');
    }
}