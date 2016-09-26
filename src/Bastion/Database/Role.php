<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Database\Traits\IsRole;
use Ethereal\Bastion\Helper;
use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Role extends Ethereal
{
    use IsRole;

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
        $this->table = config('bastion.tables.roles', 'roles');

        parent::__construct($attributes);
    }

    public function getLevel()
    {
        return $this->attributes['level'];
    }

    /**
     * Get roles assigned to authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     */
    public static function getRoles(Model $authority)
    {
        if (! $authority->exists) {
            throw new InvalidArgumentException('Authority must exist to retrieve assigned roles.');
        }

        $role = new static;
        $query = $role->newQuery();

        return $query->whereIn($role->getKeyName(), function ($query) use ($role, $authority) {
            $query
                ->select('role_id')
                ->from(Helper::getAssignedRoleTable())
                ->where('entity_id', $authority->getKey())
                ->where('entity_type', $authority->getMorphClass());
        })->get();
    }
}