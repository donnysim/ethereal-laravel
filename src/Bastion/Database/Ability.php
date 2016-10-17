<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Database\Traits\IsAbility;
use Ethereal\Database\Ethereal;

class Ability extends Ethereal
{
    use IsAbility;

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
        $this->table = config('bastion.tables.abilities', 'abilities');

        parent::__construct($attributes);
    }
}
