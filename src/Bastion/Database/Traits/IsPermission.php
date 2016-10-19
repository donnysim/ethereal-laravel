<?php

namespace Ethereal\Bastion\Database\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Ethereal\Database\Ethereal
 */
trait IsPermission
{
    /**
     * Create ability permission record.
     *
     * @param int $abilityId
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @return array
     */
    public static function createPermissionRecord($abilityId, Model $authority)
    {
        return [
            'ability_id' => $abilityId,
            'entity_id' => $authority->exists ? $authority->getKey() : null,
            'entity_type' => $authority->getMorphClass(),
        ];
    }
}
