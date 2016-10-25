<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Model;

class PermitsAbilities
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

        $abilityIds = $abilityClass::collectAbilities((array)$abilities, $model)->pluck('id');

        foreach ($this->authorities as $authority) {
            if (is_string($authority)) {
                $authority = $roleModelClass::where('name', $authority)->first();

                if (!$authority) {
                    continue;
                }
            } elseif ($authority instanceof Model && $authority->exists) {
                // TODO move to model?

                $permissionModelClass::whereIn('ability_id', $abilityIds)
                    ->where('entity_id', $authority->getKey())
                    ->where('entity_type', $authority->getMorphClass())
                    ->where('forbidden', true)
                    ->delete();
            }
        }

        $this->store->clearCache();
    }
}
