<?php

use Ethereal\Bastion\Database\Traits\HasAbilities;
use Ethereal\Database\Ethereal;

class TestArticleModel extends Ethereal
{
    use HasAbilities;

    protected $table = 'articles';

    protected $columns = ['id', 'title', 'created_at', 'updated_at'];
}
