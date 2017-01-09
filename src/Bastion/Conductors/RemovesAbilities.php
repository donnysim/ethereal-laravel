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
     * RemovesAbilities constructor.
     *
     * @param \Ethereal\Bastion\Store $store
     * @param array $authorities
     */
    public function __construct($store, array $authorities)
    {
        $this->authorities = $authorities;
        $this->store = $store;
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
        return $this->targetEverything()->to('*');
    }

    /**
     * Remove abilities from authorities.
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
                $this->removePermissionsFromAuthority($abilityClass::collectAbilities($abilities, $target)->keys());
            }
        } else {
            $this->removePermissionsFromAuthority($abilityClass::collectAbilities($abilities)->keys());
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
