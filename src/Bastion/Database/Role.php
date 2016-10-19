<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Database\Traits\HasAbilities;
use Ethereal\Bastion\Database\Traits\IsRole;
use Ethereal\Database\Ethereal;

class Role extends Ethereal
{
    use IsRole, HasAbilities;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'title'];

    /**
     * Create a new Permission model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('bastion.tables.roles', 'roles');

        parent::__construct($attributes);
    }
}
