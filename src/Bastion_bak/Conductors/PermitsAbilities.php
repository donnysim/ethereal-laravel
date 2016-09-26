<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;

class PermitsAbilities
{
    /**
     * The model to permit abilities.
     *
     * @var array
     */
    protected $authorities;

    /**
     * PermitsAbilities constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model|string $authorities
     */
    public function __construct($authorities)
    {
        $this->authorities = is_array($authorities) ? $authorities : func_get_args();
    }

    /**
     * Lift forbidden permission.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string|int $abilities
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @throws \InvalidArgumentException
     */
    public function to($abilities, $model = null)
    {
        $abilityIds = Helper::collectAbilities((array) $abilities, $model, false)->pluck('id');
        $roleClass = Helper::rolesModelClass();

        foreach ($this->authorities as $authority) {
            if (is_string($authority)) {
                $authority = $roleClass::where('name', $authority)->first();

                if (! $authority) {
                    continue;
                }
            } elseif ($authority instanceof \Illuminate\Database\Eloquent\Model && $authority->exists) {
                Helper::database()->table(Helper::permissionsTable())
                    ->whereIn('ability_id', $abilityIds)
                    ->where('entity_id', $authority->getKey())
                    ->where('entity_type', $authority->getMorphClass())
                    ->where('forbidden', true)
                    ->delete();
            }
        }
    }
}