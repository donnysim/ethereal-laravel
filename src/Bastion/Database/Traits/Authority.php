<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Helper;

/**
 * @mixin \Ethereal\Database\Ethereal
 */
trait Authority
{
    use HasAbilities, HasRoles;

    /**
     * Get abilities assigned to this authority.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities()
    {
        return Helper::bastion()->getStore()->getAbilities($this);
    }

    /**
     * Get authority map.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAuthorityMap()
    {
        return Helper::bastion()->getStore()->getMap($this);
    }
}
