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
     * Start a chain, to check if the given authority has a certain role.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @return \Ethereal\Bastion\Conductors\ChecksRoles
     */
    public function is(Model $authority)
    {
        return new ChecksRoles($authority, $this->clipboard);
    }

    /**
     * Start a chain, to allow the given authority an ability.
     *
     * @param $authorities
     * @return \Ethereal\Bastion\Conductors\GivesAbilities
     */
    public function allow($authorities)
    {
        return new GivesAbilities(func_get_args());
    }

    /**
     * Start a chain, to disallow the given authority an ability.
     *
     * @param $authorities
     * @return \Ethereal\Bastion\Conductors\RemovesAbilities
     */
    public function disallow($authorities)
    {
        return new RemovesAbilities(func_get_args());
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

    /**
     * Define a new ability using a callback.
     *
     * @param string $ability
     * @param callable|string $callback
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function define($ability, $callback)
    {
        $this->getGate()->define($ability, $callback);

        return $this;
    }

    /**
     * Determine if the given ability should be granted for the current authority.
     *
     * @param string $ability
     * @param array|mixed $arguments
     * @return bool
     */
    public function allows($ability, $arguments = [])
    {
        return $this->getGate()->allows($ability, $arguments);
    }

    /**
     * Get gate instance.
     *
     * @return \Illuminate\Contracts\Auth\Access\Gate|null
     */
    public function getGate()
    {
        return $this->gate;
    }

    /**
     * Set gate instance.
     *
     * @param \Illuminate\Contracts\Auth\Access\Gate|null $gate
     */
    public function setGate($gate)
    {
        $this->gate = $gate;
    }

    /**
     * Determine if the given ability should be denied for the current authority.
     *
     * @param string $ability
     * @param array|mixed $arguments
     * @return bool
     */
    public function denies($ability, $arguments = [])
    {
        return $this->getGate()->denies($ability, $arguments);
    }

    /**
     * Set the bouncer to be the exclusive authority on gate access.
     *
     * @param bool $boolean
     * @return $this
     */
    public function exclusive($boolean = true)
    {
        $this->clipboard->setExclusivity($boolean);

        return $this;
    }
}