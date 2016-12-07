<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Helper;

trait Authority
{
    use HasAbilities, HasRoles;

    /**
     * Get abilities assigned to this authority.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \InvalidArgumentException
     */
    public function getAbilities()
    {
        return Helper::bastion()->getStore()->getAbilities($this);
    }

    /**
     * Get authority map.
     *
     * @return \Ethereal\Bastion\Store\StoreMap
     * @throws \InvalidArgumentException
     */
    public function getAuthorityMap()
    {
        return Helper::bastion()->getStore()->getMap($this);
    }
}
