<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Helper;

trait HasAbilities
{
    /**
     * The abilities relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function abilities()
    {
        return $this->morphToMany(Helper::getAbilityModelClass(), 'target', Helper::getPermissionTable(), null, 'ability_id');
    }
}
