<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use UnexpectedValueException;

class RemovesAbilities
{
    use Traits\CollectsAuthorities,
        Traits\UsesScopes;

    /**
     * Authorities to remove abilities from.
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
     * Determine if the removed ability is forbidden.
     *
     * @var bool
     */
    protected $forbidden = false;

    /**
     * RemovesAbilities constructor.
     *
     * @param \Ethereal\Bastion\Store $store
     * @param array $authorities
     * @param bool $forbidden
     */
    public function __construct($store, array $authorities, $forbidden = false)
    {
        $this->authorities = $authorities;
        $this->store = $store;
        $this->forbidden = $forbidden;
    }

    /**
     * Set whether the given abilities should forbid.
     *
     * @param bool $value
     */
    public function forbidden($value = true)
    {
        $this->forbidden = $value;
    }

    /**
     * Disallow everything.
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
     * Remove abilities from authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string $abilities
     * @param \Illuminate\Database\Eloquent\Model|array|string|null $modelListOrClass
     * @param array|string|int|null $ids
     *
     * @return $this
     * @throws \UnexpectedValueException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function to($abilities, $modelListOrClass = null, $ids = null)
    {
        /** @var \Ethereal\Bastion\Database\Ability $abilityClass */
        $abilityClass = Helper::getAbilityModelClass();

        if ($modelListOrClass === null && $ids === null) {
            $this->removePermissionsFromAuthority($abilityClass::collectAbilities((array)$abilities)->keys());
        } else {
            foreach ($this->getTargets($modelListOrClass, $ids) as $target) {
                $this->removePermissionsFromAuthority($abilityClass::collectAbilities((array)$abilities, $target)->keys());
            }
        }

        $this->store->clearCache();

        return $this;
    }

    /**
     * Create permissions for authorities.
     *
     * @param \Illuminate\Support\Collection $abilityIds
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function removePermissionsFromAuthority($abilityIds)
    {
        if ($abilityIds->isEmpty()) {
            return;
        }

        /** @var \Ethereal\Bastion\Database\Role $roleModelClass */
        $roleModelClass = Helper::getRoleModelClass();

        foreach ($this->authorities as $authority) {
            if (is_string($authority)) {
                /** @var \Ethereal\Bastion\Database\Traits\HasAbilities $authority */
                $authority = $roleModelClass::collectRoles([$authority])->first();
            }

            $query = $authority->abilities()->newPivotStatement()
                ->where('forbidden', $this->forbidden)
                ->where('group', $this->scopeGroup)
                ->whereIn('ability_id', $abilityIds->all());

            if ($this->scopeParent) {
                $query
                    ->where('parent_id', $this->scopeParent->getKey())
                    ->where('parent_type', $this->scopeParent->getMorphClass());
            } else {
                $query
                    ->whereNull('parent_id')
                    ->whereNull('parent_type');
            }

            $query->delete();
        }
    }
}
