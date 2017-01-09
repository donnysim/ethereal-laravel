<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;

class GivesAbilities
{
    use Traits\CollectsAuthorities,
        Traits\UsesScopes;

    /**
     * Authorities to give abilities to.
     *
     * @var array
     */
    protected $authorities = [];

    /**
     * Permission store.
     *
     * @var \Ethereal\Bastion\Store
     */
    protected $store;

    /**
     * Determine if the given ability forbids it.
     *
     * @var bool
     */
    protected $forbidden = false;

    /**
     * GivesAbilities constructor.
     *
     * @param \Ethereal\Bastion\Store $store
     * @param array $authorities
     * @param bool $forbids
     */
    public function __construct($store, array $authorities, $forbids = false)
    {
        $this->authorities = $authorities;
        $this->store = $store;
        $this->forbidden = $forbids;
    }

    /**
     * Set whether the given abilities should forbid.
     *
     * @param bool $value
     */
    public function forbids($value = true)
    {
        $this->forbidden = $value;
    }

    /**
     * Allow everything.
     *
     * @return $this
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function everything()
    {
        return $this->to('*', '*');
    }

    /**
     * Give abilities to authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string $abilities
     * @param \Illuminate\Database\Eloquent\Model|array|string|null $modelListOrClass
     * @param array|string|int|null $ids
     *
     * @return $this
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function to($abilities, $modelListOrClass = null, $ids = null)
    {
        /** @var \Ethereal\Bastion\Database\Ability $abilityClass */
        $abilityClass = Helper::getAbilityModelClass();

        if ($modelListOrClass === null && $ids === null) {
            $this->assignPermissionsToAuthority($abilityClass::collectAbilities((array)$abilities)->keys());
        } else {
            foreach ($this->getTargets($modelListOrClass, $ids) as $target) {
                $this->assignPermissionsToAuthority($abilityClass::collectAbilities((array)$abilities, $target)->keys());
            }
        }

        // TODO clear cache
        return $this;
    }

    /**
     * Create permissions for authorities.
     *
     * @param \Illuminate\Support\Collection $abilityIds
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function assignPermissionsToAuthority($abilityIds)
    {
        if ($abilityIds->isEmpty()) {
            return;
        }

        /** @var \Ethereal\Bastion\Database\Role $roleModelClass */
        $roleModelClass = Helper::getRoleModelClass();
        $abilityKeyName = Helper::getAbilityModel()->getKeyName();
        /** @var \Ethereal\Bastion\Database\Permission $permissionModelClass */
        $permissionModelClass = Helper::getPermissionModelClass();

        foreach ($this->authorities as $authority) {
            if (is_string($authority)) {
                $authority = $roleModelClass::collectRoles([$authority])->first();
            }

            $missingAbilities = $abilityIds->diff($authority->abilities()->whereIn($abilityKeyName, $abilityIds)->pluck('id'));
            foreach ($missingAbilities as $abilityId) {
                $permissionModelClass::createPermissionRecord($abilityId, $authority, $this->scopeGroup, $this->forbidden, $this->scopeParent);
            }
        }
    }

}
