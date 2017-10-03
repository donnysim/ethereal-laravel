<?php

namespace Ethereal\Bastion;

use Ethereal\Bastion\Conductors\AssignsPermissions;
use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\ChecksRoles;
use Ethereal\Bastion\Conductors\RemovesPermissions;
use Ethereal\Bastion\Conductors\RemovesRoles;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;

class Bastion
{
    /**
     * Default guard.
     *
     * @var string
     */
    protected static $useGuard = 'default';

    /**
     * Permissions cache.
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $cache;

    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Guard to use by default.
     *
     * @var string
     */
    protected $guard;

    /**
     * Ruck instances.
     *
     * @var array
     */
    protected $rucks = [];

    /**
     * Create a new rucks instance.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @param \Illuminate\Contracts\Cache\Store $cache
     * @param string|null $guard
     */
    public function __construct(Container $container, $cache, $guard = null)
    {
        $this->container = $container;
        $this->cache = $cache;
        $this->guard = $guard ?: static::$useGuard;
    }

    /**
     * Disable cache.
     */
    public static function disableCache()
    {
        Store::disableCache();
    }

    /**
     * Enable cache.
     */
    public static function enableCache()
    {
        Store::enableCache();
    }

    /**
     * Set the guard Bastion should use.
     *
     * @param string $guard
     */
    public static function shouldUse($guard)
    {
        static::$useGuard = $guard ?: 'default';
    }

    /**
     * Start a chain to assign the given role to authority.
     *
     * @param array|\Illuminate\Database\Eloquent\Model $authorities
     *
     * @return \Ethereal\Bastion\Conductors\AssignsPermissions
     */
    public function allow($authorities)
    {
        return new AssignsPermissions($this->getStore(), \is_array($authorities) ? $authorities : \func_get_args());
    }

    /**
     * Start a chain to assign the given role to authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @return \Ethereal\Bastion\Conductors\AssignsRoles
     */
    public function assign($roles)
    {
        return new AssignsRoles($this->getStore(), \is_array($roles) ? $roles : \func_get_args());
    }

    /**
     * Start a chain to remove permissions from authorities.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $authorities
     *
     * @return \Ethereal\Bastion\Conductors\RemovesPermissions
     */
    public function disallow($authorities)
    {
        return new RemovesPermissions($this->getStore(), \is_array($authorities) ? $authorities : \func_get_args());
    }

    /**
     * Bastion instance for specific guard.
     *
     * @param string $guard
     *
     * @return \Ethereal\Bastion\Bastion
     */
    public function forGuard($guard)
    {
        return new static($this->container, $this->cache, $guard);
    }

    /**
     * Return currently used guard.
     *
     * @return string
     */
    public function getGuard()
    {
        return $this->guard;
    }

    /**
     * Get store.
     *
     * @param string|null $guard
     *
     * @return \Ethereal\Bastion\Store
     */
    public function getStore($guard = null)
    {
        $guard = $guard ?: $this->guard;

        return \tap(new Store($guard), function (Store $store) {
            $store->setCache($this->cache);
        });
    }

    /**
     * Start a chain to check role of authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Ethereal\Bastion\Conductors\ChecksRoles
     */
    public function is(Model $authority)
    {
        return new ChecksRoles($this->getStore(), $authority);
    }

    /**
     * Start a chain to remove the given role from authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @return \Ethereal\Bastion\Conductors\RemovesRoles
     */
    public function retract($roles)
    {
        return new RemovesRoles($this->getStore(), \is_array($roles) ? $roles : \func_get_args());
    }

    /**
     * Get or initiate a new Rucks instance.
     *
     * @param string|null $guard
     *
     * @return \Ethereal\Bastion\Rucks
     */
    public function rucks($guard = null)
    {
        if (!$guard) {
            $guard = static::$useGuard;
        }

        if (!isset($this->rucks[$guard])) {
            $this->rucks[$guard] = new Rucks($this->container, $this->getStore($guard));
        }

        return $this->rucks[$guard];
    }
}
