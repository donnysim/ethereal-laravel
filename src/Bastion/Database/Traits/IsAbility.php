<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @mixin \Ethereal\Database\Ethereal
 */
trait IsAbility
{
    /**
     * Get status if the ability forbids permission.
     *
     * @return bool
     */
    public function isForbidden()
    {
        return (bool) $this->attributes['forbidden'];
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
     * Get abilities assigned to authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param \Illuminate\Database\Eloquent\Collection|null $roles
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAbilities(Model $authority, Collection $roles = null)
    {
        if (! $authority->exists) {
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
        if ($roles !== null && ! $roles->isEmpty()) {
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
}
