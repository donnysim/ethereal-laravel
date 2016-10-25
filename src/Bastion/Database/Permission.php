<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Database\Traits\IsPermission;
use Ethereal\Database\Ethereal;

class Permission extends Ethereal
{
    use IsPermission;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['forbidden'];

    /**
     * Create a new Permission model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('bastion.tables.permissions', 'permissions');

        parent::__construct($attributes);
    }
}
