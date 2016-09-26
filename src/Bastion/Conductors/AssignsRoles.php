<?php

namespace Bastion\Conductors;

class AssignsRoles
{
    /**
     * List of roles to assign to authority.
     *
     * @var array|string
     */
    protected $roles;

    /**
     * AssignsRole constructor.
     *
     * @param string|int|array $roles
     */
    public function __construct($roles)
    {
        $this->roles = is_array($roles) ? $roles : func_get_args();
    }
}