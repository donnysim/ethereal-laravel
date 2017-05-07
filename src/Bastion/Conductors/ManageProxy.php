<?php

namespace Ethereal\Bastion\Conductors;

use Illuminate\Database\Eloquent\Model;

class ManageProxy
{
    use Traits\UsesScopes;

    /**
     * Permission store.
     *
     * @var \Ethereal\Bastion\Store
     */
    protected $store;

    /**
     * The authority against which to check for roles.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $authority;

    /**
     * ManageProxy constructor.
     *
     * @param \Ethereal\Bastion\Store $store
     * @param \Illuminate\Database\Eloquent\Model $authority
     */
    public function __construct($store, Model $authority)
    {
        $this->store = $store;
        $this->authority = $authority;
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
        (new AssignsRoles($this->store, is_array($roles) ? $roles : func_get_args()))->to($this->authority);
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
        (new RemovesRoles($this->store, is_array($roles) ? $roles : func_get_args()))->from($this->authority);
    }

    /**
     * Give abilities to authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string $abilities
     * @param \Illuminate\Database\Eloquent\Model|array|string|null $modelListOrClass
     * @param array|string|int|null $ids
     *
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function allow($abilities, $modelListOrClass = null, $ids = null)
    {
        (new GivesAbilities($this->store, [$this->authority], false))
            ->parent($this->scopeParent)
            ->to($abilities, $modelListOrClass, $ids);
    }

    /**
     * Remove abilities from authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string $abilities
     * @param \Illuminate\Database\Eloquent\Model|array|string|null $modelListOrClass
     * @param array|string|int|null $ids
     *
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \UnexpectedValueException
     */
    public function disallow($abilities, $modelListOrClass = null, $ids = null)
    {
        (new RemovesAbilities($this->store, [$this->authority], false))
            ->parent($this->scopeParent)
            ->to($abilities, $modelListOrClass, $ids);
    }

    /**
     * Forbid abilities to authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string $abilities
     * @param \Illuminate\Database\Eloquent\Model|array|string|null $modelListOrClass
     * @param array|string|int|null $ids
     *
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function forbid($abilities, $modelListOrClass = null, $ids = null)
    {
        (new GivesAbilities($this->store, [$this->authority], true))
            ->parent($this->scopeParent)
            ->to($abilities, $modelListOrClass, $ids);
    }

    /**
     * Permit forbidden abilities from authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string $abilities
     * @param \Illuminate\Database\Eloquent\Model|array|string|null $modelListOrClass
     * @param array|string|int|null $ids
     *
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \UnexpectedValueException
     */
    public function permit($abilities, $modelListOrClass = null, $ids = null)
    {
        (new RemovesAbilities($this->store, [$this->authority], true))
            ->parent($this->scopeParent)
            ->to($abilities, $modelListOrClass, $ids);
    }
}
