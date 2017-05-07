<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\ChecksAbilities;
use Ethereal\Bastion\Conductors\ChecksRoles;
use Ethereal\Bastion\Conductors\GivesAbilities;
use Ethereal\Bastion\Conductors\RemovesAbilities;
use Ethereal\Bastion\Conductors\RemovesRoles;
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

    /**
     * Alias to a method.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isAn($role)
    {
        return $this->isA(is_array($role) ? $role : func_get_args());
    }

    /**
     * Determine if authority has one of the roles.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isA($role)
    {
        return (new ChecksRoles(Helper::bastion()->getStore(), $this))->a(is_array($role) ? $role : func_get_args());
    }

    /**
     * Alias to notA method.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isNotAn($role)
    {
        return $this->isNotA(is_array($role) ? $role : func_get_args());
    }

    /**
     * Determine if authority does not have one of the roles.
     *
     * @param array|string $role
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isNotA($role)
    {
        return (new ChecksRoles(Helper::bastion()->getStore(), $this))->notA(is_array($role) ? $role : func_get_args());
    }

    /**
     * Determine if authority has the ability.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param \Illuminate\Database\Eloquent\Model|null $parent
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function can($ability, $model = null, $parent = null)
    {
        return (new ChecksAbilities(Helper::bastion()->getStore(), $this))
            ->parent($parent)
            ->can($ability, $model);
    }

    /**
     * Determine if authority does not have the ability.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param \Illuminate\Database\Eloquent\Model|null $parent
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function cannot($ability, $model = null, $parent = null)
    {
        return !$this->can($ability, $model, $parent);
    }

    /**
     * Determine if the ability is allowed for the current user.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null|array $model
     * @param array $payload
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function allowed($ability, $model = null, $payload = [])
    {
        return Helper::bastion()->allows($ability, $model, $payload);
    }

    /**
     * Determine if the ability is denied for the current user.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null|array $model
     * @param array $payload
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function denied($ability, $model = null, $payload = [])
    {
        return !Helper::bastion()->allows($ability, $model, $payload);
    }

    /**
     * Assign the given role to authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function assign($roles)
    {
        (new AssignsRoles(Helper::bastion()->getStore(), is_array($roles) ? $roles : func_get_args()))->to($this);
    }

    /**
     * Remove the given role from authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function retract($roles)
    {
        (new RemovesRoles(Helper::bastion()->getStore(), is_array($roles) ? $roles : func_get_args()))->from($this);
    }

    /**
     * Give abilities to authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string $abilities
     * @param \Illuminate\Database\Eloquent\Model|array|string|null $modelListOrClass
     * @param array|string|int|null $ids
     * @param \Illuminate\Database\Eloquent\Model|null $parent
     *
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function allow($abilities, $modelListOrClass = null, $ids = null, $parent = null)
    {
        (new GivesAbilities(Helper::bastion()->getStore(), [$this], false))
            ->parent($parent)
            ->to($abilities, $modelListOrClass, $ids);
    }

    /**
     * Remove abilities from authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string $abilities
     * @param \Illuminate\Database\Eloquent\Model|array|string|null $modelListOrClass
     * @param array|string|int|null $ids
     * @param \Illuminate\Database\Eloquent\Model|null $parent
     *
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \UnexpectedValueException
     */
    public function disallow($abilities, $modelListOrClass = null, $ids = null, $parent = null)
    {
        (new RemovesAbilities(Helper::bastion()->getStore(), [$this], false))
            ->parent($parent)
            ->to($abilities, $modelListOrClass, $ids);
    }

    /**
     * Forbid abilities to authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string $abilities
     * @param \Illuminate\Database\Eloquent\Model|array|string|null $modelListOrClass
     * @param array|string|int|null $ids
     * @param \Illuminate\Database\Eloquent\Model|null $parent
     *
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function forbid($abilities, $modelListOrClass = null, $ids = null, $parent = null)
    {
        (new GivesAbilities(Helper::bastion()->getStore(), [$this], true))
            ->parent($parent)
            ->to($abilities, $modelListOrClass, $ids);
    }

    /**
     * Permit forbidden abilities from authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string $abilities
     * @param \Illuminate\Database\Eloquent\Model|array|string|null $modelListOrClass
     * @param array|string|int|null $ids
     * @param \Illuminate\Database\Eloquent\Model|null $parent
     *
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \UnexpectedValueException
     */
    public function permit($abilities, $modelListOrClass = null, $ids = null, $parent = null)
    {
        (new RemovesAbilities(Helper::bastion()->getStore(), [$this], true))
            ->parent($parent)
            ->to($abilities, $modelListOrClass, $ids);
    }

}
