<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Helper;
use Ethereal\Database\Ethereal;

class Permission extends Ethereal
{
    use Traits\IsPermission;

    /**
     * Database columns. This is used to filter out invalid columns.
     *
     * @var string[]
     */
    protected $columns = ['ability_id', 'target_id', 'target_type', 'parent_id', 'parent_type', 'forbidden', 'created_at', 'updated_at'];

    /**
     * Create a new Permission model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = Helper::getPermissionTable();

        parent::__construct($attributes);
    }
}
