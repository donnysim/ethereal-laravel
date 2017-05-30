<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Helper;
use Ethereal\Database\Ethereal;

class Role extends Ethereal
{
    use Traits\IsRole,
        Traits\HasAbilities;

    /**
     * Database columns. This is used to filter out invalid columns.
     *
     * @var string[]
     */
    protected $columns = ['id', 'name', 'title', 'system', 'private', 'level', 'created_at', 'updated_at'];

    /**
     * Create a new Role model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = Helper::getRoleTable();

        parent::__construct($attributes);
    }
}
