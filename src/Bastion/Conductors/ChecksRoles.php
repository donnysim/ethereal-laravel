<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Model;

class ChecksRoles
{
    /**
     * The authority against which to check for roles.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $authority;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     */
    public function __construct(Model $authority)
    {
        $this->authority = $authority;
    }

    /**
     * Check if the authority has any of the given roles.
     *
     * @param string $role
     * @return bool
     */
    public function a($role)
    {
        $roles = func_get_args();

        return Helper::clipboard()->checkRole($this->authority, $roles, 'or');
    }

    /**
     * Check if the authority doesn't have any of the given roles.
     *
     * @param string $role
     * @return bool
     */
    public function notA($role)
    {
        $roles = func_get_args();

        return Helper::clipboard()->checkRole($this->authority, $roles, 'not');
    }

    /**
     * Alias to the "a" method.
     *
     * @param string $role
     * @return bool
     */
    public function an($role)
    {
        $roles = func_get_args();

        return Helper::clipboard()->checkRole($this->authority, $roles, 'or');
    }

    /**
     * Alias to the "notA" method.
     *
     * @param string $role
     * @return bool
     */
    public function notAn($role)
    {
        $roles = func_get_args();

        return Helper::clipboard()->checkRole($this->authority, $roles, 'not');
    }

    /**
     * Check if the authority has all of the given roles.
     *
     * @param string $role
     * @return bool
     */
    public function all($role)
    {
        $roles = func_get_args();

        return Helper::clipboard()->checkRole($this->authority, $roles, 'and');
    }
}