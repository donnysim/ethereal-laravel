<?php

namespace Ethereal\Bastion\Conductors\Traits;

use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\GivesAbilities;
use Ethereal\Bastion\Conductors\RemovesAbilities;
use Ethereal\Bastion\Conductors\RemovesRoles;

trait ManagesAuthority
{
    /**
     * Start a chain to assign the given role to authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @return \Ethereal\Bastion\Conductors\AssignsRoles
     */
    public function assign($roles)
    {
        return new AssignsRoles($this->getStore(), is_array($roles) ? $roles : func_get_args());
    }

    /**
     * Start a chain to remove the given role from authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @return \Ethereal\Bastion\Conductors\RemovesRoles
     */
    public function retract($roles)
    {
        return new RemovesRoles($this->getStore(), is_array($roles) ? $roles : func_get_args());
    }

    /**
     * Start a chain to give abilities to authorities.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $authorities
     *
     * @return \Ethereal\Bastion\Conductors\GivesAbilities
     */
    public function allow($authorities)
    {
        return new GivesAbilities($this->getStore(), is_array($authorities) ? $authorities : func_get_args(), false);
    }

    /**
     * Start a chain to remove abilities from authorities.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $authorities
     *
     * @return \Ethereal\Bastion\Conductors\RemovesAbilities
     */
    public function disallow($authorities)
    {
        return new RemovesAbilities($this->getStore(), is_array($authorities) ? $authorities : func_get_args(), false);
    }

    /**
     * Start a chain to forbid abilities to authorities.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $authorities
     *
     * @return \Ethereal\Bastion\Conductors\GivesAbilities
     */
    public function forbid($authorities)
    {
        return new GivesAbilities($this->getStore(), is_array($authorities) ? $authorities : func_get_args(), true);
    }

    /**
     * Start a chain to permit forbidden abilities from authorities.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $authorities
     *
     * @return \Ethereal\Bastion\Conductors\RemovesAbilities
     */
    public function permit($authorities)
    {
        return new RemovesAbilities($this->getStore(), is_array($authorities) ? $authorities : func_get_args(), true);
    }
}
