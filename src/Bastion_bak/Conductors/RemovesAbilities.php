<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;

class RemovesAbilities
{
    /**
     * The model to remove abilities from.
     *
     * @var array
     */
    protected $authorities;

    /**
     * RemovesAbilities constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model|string $authorities
     */
    public function __construct($authorities)
    {
        $this->authorities = is_array($authorities) ? $authorities : func_get_args();
    }

    /**
     * Remove abilities from authorities.
     *
     * @param \Illuminate\Database\Eloquent\Model|array|string|int $abilities
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     */
    public function to($abilities, $model = null)
    {
        $abilityIds = Helper::collectAbilities((array) $abilities, $model, false)->pluck('id');
        $rolesModelClass = Helper::rolesModelClass();

        if (empty($abilityIds)) {
            return;
        }

        foreach ($this->authorities as $authority) {
            if (is_string($authority)) {
                $authority = $rolesModelClass::where('name', $authority)->first();

                if (! $authority) {
                    continue;
                }
            }

            $authority->abilities()->detach($abilityIds->all());
        }
    }
}