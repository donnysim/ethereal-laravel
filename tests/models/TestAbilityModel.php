<?php

use Ethereal\Database\Ethereal;

class TestAbilityModel extends Ethereal
{
    use \Ethereal\Bastion\Database\Traits\IsAbility;

    protected $table = 'abilities';

    protected $guarded = [];
}
