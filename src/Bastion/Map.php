<?php

namespace Ethereal\Bastion;

class Map
{
    /**
     * Role collections.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $roles;

    /**
     * Ability collection.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $abilities;

    /**
     * Allowed abilities list.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $allowedAbilities;

    /**
     * Forbidden abilities list.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $forbiddenAbilities;

    /**
     * Highest role level. Lower is higher.
     *
     * @var int
     */
    protected $highestRoleLevel = 0;

    /**
     * Lowest role level. Higher is lower.
     *
     * @var int
     */
    protected $lowestRoleLevel = 0;
}
