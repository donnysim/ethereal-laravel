<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Helper;

trait Authority
{
    use HasRoles, HasAbilities;

    /**
     * Get authority permissions.
     *
     * @return \Ethereal\Bastion\Map
     * @throws \InvalidArgumentException
     */
    public function permissions()
    {
        return Helper::bastion()->permissions($this);
    }
}
