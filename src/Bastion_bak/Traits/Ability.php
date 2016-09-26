<?php

namespace Ethereal\Bastion\Traits;

use Ethereal\Bastion\Helper;

trait Ability
{
    /**
     * Get ability name.
     *
     * @return string
     */
    public function getAbilityName()
    {
        return $this->attributes['name'];
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
            $slug .= '-' . $this->attributes['entity_type'];
        }

        if ($this->attributes['entity_id']) {
            $slug .= '-' . $this->attributes['entity_id'];
        }

        return strtolower($slug);
    }

    /**
     * The roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function roles()
    {
        return $this->morphedByMany(Helper::rolesModelClass(), 'entity', Helper::permissionsTable());
    }
}