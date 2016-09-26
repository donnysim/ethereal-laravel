<?php

namespace Ethereal\Bastion;

use Ethereal\Bastion\Store\Store;
use Illuminate\Contracts\Auth\Access\Gate;

class Bastion
{
    /**
     * The bouncer clipboard instance.
     *
     * @var Store
     */
    protected $store;

    /**
     * The access gate instance.
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    protected $gate;

    /**
     * Bastion constructor.
     *
     * @param \Illuminate\Contracts\Auth\Access\Gate $gate
     * @param \Ethereal\Bastion\Store\Store $clipboard
     */
    public function __construct(Gate $gate, Store $clipboard)
    {
        $this->gate = $gate;
        $this->clipboard = $clipboard;
    }
}