<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use UnexpectedValueException;

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
        return $this->targetEverything()->to('*');
    }

    /**
     * Give abilities to authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string ...$abilities
     *
     * @return $this
     * @throws \UnexpectedValueException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function to(...$abilities)
    {
        /** @var \Ethereal\Bastion\Database\Ability $abilityClass */
        $abilityClass = Helper::getAbilityModelClass();

        if ($this->targeted && empty($this->scopeTargets)) {
            throw new UnexpectedValueException('Models were targeted but the list is empty.');
        }

        if ($this->targeted) {
            foreach ($this->scopeTargets as $target) {
                $this->assignPermissionsToAuthority($abilityClass::collectAbilities($abilities, $target)->keys());
            }
        } else {
            $this->assignPermissionsToAuthority($abilityClass::collectAbilities($abilities)->keys());
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
