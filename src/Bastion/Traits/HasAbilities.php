<?php

namespace Ethereal\Bastion\Traits;

use Ethereal\Bastion\Conductors\GivesAbilities;
use Ethereal\Bastion\Conductors\RemovesAbilities;
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
        return $this->morphToMany(Helper::abilityModelClass(), 'entity', Helper::permissionsTable());
    }

    /**
     * Get all of the model's abilities.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities()
    {
        return Helper::clipboard()->getAbilities($this);
    }

    /**
     * Get all of the model's abilities grouped by allowed and forbidden.
     * The returned value is an array [allowed, forbidden].
     *
     * @return array
     */
    public function getAbilitiesMap()
    {
        return Helper::clipboard()->getAbilityMap($this);
    }

    /**
     * Give abilities to the model.
     *
     * @param $ablitity
     * @param null $model
     * @return $this
     */
    public function allow($ablitity, $model = null)
    {
        (new GivesAbilities($ablitity))->to($ablitity, $model);

        return $this;
    }

    /**
     * Removes abilities from the model.
     *
     * @param $ablitity
     * @param null $model
     * @return $this
     */
    public function disallow($ablitity, $model = null)
    {
        (new RemovesAbilities($ablitity))->to($ablitity, $model);

        return $this;
    }

}