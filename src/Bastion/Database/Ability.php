<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Database\Traits\IsAbility;
use Ethereal\Bastion\Helper;
use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Ability extends Ethereal
{
    use IsAbility;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'title'];

    /**
     * Create a new Permission model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('bastion.tables.abilities', 'abilities');

        parent::__construct($attributes);
    }

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

        if ($this->attributes['only_owned']) {
            $slug .= '-owned';
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
        $query = $ability->newQuery();

        $permissionTable = Helper::getPermissionTable();

        $query
            // Join permissions
            ->join($permissionTable, "{$permissionTable}.ability_id", '=', $ability->getQualifiedKeyName())
            // Apply authority constraints
            ->where(function ($query) use ($permissionTable, $authority) {
                $query
                    ->where("{$permissionTable}.entity_id", $authority->getKey())
                    ->where("{$permissionTable}.entity_type", $authority->getMorphClass());
            });

        // Apply roles constraints
        if ($roles !== null && ! $roles->isEmpty()) {
            $query->orWhere(function ($query) use ($permissionTable, $authority, $roles) {
                $role = Helper::getRoleModel();

                $query
                    ->whereIn("{$permissionTable}.entity_id", $roles->pluck($role->getKeyName()))
                    ->whereIn("{$permissionTable}.entity_type", $role->getMorphClass());
            });
        }

        return $query->get(['abilities.*', "{$permissionTable}.forbidden"]);
    }
}