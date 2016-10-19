<?php

namespace Ethereal\Bastion;

use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\ChecksRoles;
use Ethereal\Bastion\Conductors\DeniesAbilities;
use Ethereal\Bastion\Conductors\GivesAbilities;
use Ethereal\Bastion\Conductors\PermitsAbilities;
use Ethereal\Bastion\Conductors\RemovesAbilities;
use Ethereal\Bastion\Conductors\RemovesRoles;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Model;

class Bastion
{
    /**
     * The bouncer clipboard instance.
     *
     * @var Clipboard|CachedClipboard
     */
    protected $clipboard;

    /**
     * The access gate instance.
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate|null
     */
    protected $gate;

    /**
     * Object sanitizer.
     *
     * @var \Ethereal\Bastion\Sanitizer
     */
    private $sanitizer;

    /**
     * Bastion constructor.
     *
     * @param \Illuminate\Contracts\Auth\Access\Gate|null $gate
     * @param \Ethereal\Bastion\Clipboard $clipboard
     * @param \Ethereal\Bastion\Sanitizer $sanitizer
     */
    public function __construct(Gate $gate, Clipboard $clipboard, Sanitizer $sanitizer)
    {
        $this->gate = $gate;
        $this->clipboard = $clipboard;
        $this->sanitizer = $sanitizer;
    }

    /**
     * Start a chain, to forbid the given authority an ability.
     *
     * @param $authorities
     * @return \Ethereal\Bastion\Conductors\DeniesAbilities
     */
    public function forbid($authorities)
    {
        return new DeniesAbilities(func_get_args());
    }

    /**
     * Start a chain, to forbid the given authority an ability.
     *
     * @param $authorities
     * @return \Ethereal\Bastion\Conductors\PermitsAbilities
     */
    public function permit($authorities)
    {
        return new PermitsAbilities(func_get_args());
    }

}
