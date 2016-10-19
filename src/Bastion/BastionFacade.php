<?php

namespace Ethereal\Bastion;

use Illuminate\Support\Facades\Facade;

class BastionFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Bastion::class;
    }
}
