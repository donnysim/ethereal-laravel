<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;

class GivesAbilities
{
    /**
     * List of authorities to give the abilities.
     *
     * @var array|string
     */
    protected $authorities;

    /**
     * Permission store.
     *
     * @var \Ethereal\Bastion\Store\Store
     */
    protected $store;

    /**
     * AssignsRole constructor.
     *
     * @param \Ethereal\Bastion\Store\Store $store
     * @param string|int|array $authorities
     */
    public function __construct($store, $authorities)
    {
        $this->authorities = $authorities;
        $this->store = $store;
    }

    /**
     * Give abilities to authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string|int $abilities
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     */
    public function to($abilities, $model = null)
    {
        /** @var \Ethereal\Bastion\Database\Ability $abilityClass */
        $abilityClass = Helper::getAbilityModelClass();
        /** @var \Ethereal\Bastion\Database\Role $roleModelClass */
        $roleModelClass = Helper::getRoleModelClass();
        /** @var \Ethereal\Bastion\Database\Permission $permissionModelClass */
        $permissionModelClass = Helper::getPermissionModelClass();

        $abilityIds = $abilityClass::collectAbilities((array) $abilities, $model)->pluck('id');

        foreach ($this->authorities as $authority) {
            if (is_string($authority)) {
                // TODO move to role model?
                $authority = $roleModelClass::firstOrCreate([
                    'name' => $authority,
                ]);
            }

            $missingAbilities = $abilityIds->diff($authority->abilities()->whereIn('id', $abilityIds->all())->pluck('id'));
            $inserts = [];

            foreach ($missingAbilities as $abilityId) {
                $inserts[] = $permissionModelClass::createPermissionRecord($abilityId, $authority);
            }

            $permissionModelClass::insert($inserts);
        }

        $this->store->clearCache();
    }
}
