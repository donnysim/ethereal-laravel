<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Helper;
use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @method Ability joinPermissions()
 * @method Ability forAuthority(Model $authority, $method = 'where')
 * @method Ability ofRoles(Model $authority, $method = 'where')
 */
class Ability extends Ethereal
{
    use Traits\IsAbility;

    protected $columns = ['id', 'name', 'entity_id', 'entity_type', 'created_at', 'updated_at'];
}
