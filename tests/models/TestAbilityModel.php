<?php

use Ethereal\Bastion\Database\Traits\IsAbility;
use Ethereal\Database\Ethereal;

class TestAbilityModel extends Ethereal
{
    use IsAbility;

    protected $table = 'abilities';

    protected $guarded = [];
}
