<?php

use Ethereal\Database\Ethereal;

class TestAbilityModel extends Ethereal
{
    use \Ethereal\Bastion\Traits\Ability;

    protected $table = 'abilities';

    protected $guarded = [];
}