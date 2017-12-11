<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasRoles
{
    /**
     * The roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(Helper::getRoleModelClass(), 'model', Helper::getAssignedRolesTable(), 'model_id', 'role_id')
            ->where('guard', \app('bastion')->getGuard());
    }

    /**
     * Add profiles table to the query.
     *
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return mixed
     */
    public function scopeJoinRoles($query)
    {
        $art = Helper::getAssignedRolesTable();
        $rt = Helper::getRolesTable();

        return $query->join($art, function ($join) use ($art) {
            $join->on("$art.model_id", '=', "{$this->getTable()}.{$this->getKeyName()}")
                ->where("$art.model_type", $this->getMorphClass());
        })->join($rt, "$rt.id", '=', "$art.role_id");
    }
}
