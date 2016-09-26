<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;

class GivesAbilities
{
    /**
     * The model to be given abilities.
     *
     * @var \Illuminate\Database\Eloquent\Model[]
     */
    protected $authorities;

    /**
     * GivesAbilities constructor.
     *
     * @param $authorities
     */
    public function __construct($authorities)
    {
        $this->authorities = is_array($authorities) ? $authorities : func_get_args();
    }

    /**
     * Give abilities to authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string|int $abilities
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     */
    public function to($abilities, $model = null)
    {
        $abilityIds = Helper::collectAbilities((array) $abilities, $model)->pluck('id');
        $rolesModelClass = Helper::rolesModelClass();

        foreach ($this->authorities as $authority) {
            if (is_string($authority)) {
                $authority = $rolesModelClass::firstOrCreate([
                    'name' => $authority,
                ]);
            }

            $missingAbilities = $abilityIds->diff($authority->abilities()->whereIn('id', $abilityIds)->pluck('id'));
            $inserts = [];

            foreach ($missingAbilities as $abilityId) {
                $inserts[] = [
                    'ability_id' => $abilityId,
                    'entity_id' => $authority->exists ? $authority->getKey() : null,
                    'entity_type' => $authority->getMorphClass(),
                ];
            }

            Helper::database()->table(Helper::permissionsTable())->insert($inserts);
        }
    }
}