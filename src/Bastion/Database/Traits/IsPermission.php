<?php

namespace Ethereal\Bastion\Database\Traits;

use Illuminate\Database\Eloquent\Model;

trait IsPermission
{
    /**
     * Create ability permission record.
     *
     * @param int $abilityId
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param bool $forbids
     *
     * @return array
     */
    public static function createPermissionRecord($abilityId, Model $authority, $forbids = false)
    {
        return [
            'ability_id' => $abilityId,
            'entity_id' => $authority->exists ? $authority->getKey() : null,
            'entity_type' => $authority->getMorphClass(),
            'forbidden' => $forbids,
        ];
    }
}
