<?php

namespace Ethereal\Bastion;

use Ethereal\Bastion\Conductors\AssignsPermissions;
use Ethereal\Bastion\Conductors\AssignsRoles;
use Ethereal\Bastion\Conductors\ChecksPermissions;
use Ethereal\Bastion\Conductors\ChecksRoles;
use Ethereal\Bastion\Conductors\ForbidsPermissions;
use Ethereal\Bastion\Conductors\PermitsPermissions;
use Ethereal\Bastion\Conductors\RemovesPermissions;
use Ethereal\Bastion\Conductors\RemovesRoles;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;

class Bastion
{
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
    protected $guard = 'default';

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
     */
    public function __construct(Container $container, $cache)
    {
        $this->container = $container;
        $this->cache = $cache;
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
     * Determine if authority has permission.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     * @param string $permission
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     *
     * @return bool
     */
    public function can(Model $authority, $permission, $model = null): bool
    {
        return (new ChecksPermissions(\app('bastion')->store(), $authority))->can($permission, $model);
    }

    /**
     * Start a chain to assign permissions to authority.
     *
     * @param array|\Illuminate\Database\Eloquent\Model $authorities
     *
     * @return \Ethereal\Bastion\Conductors\AssignsPermissions
     */
    public function allow($authorities): AssignsPermissions
    {
        return new AssignsPermissions($this->store(), \is_array($authorities) ? $authorities : \func_get_args());
    }

    /**
     * Start a chain to assign the given role to authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @return \Ethereal\Bastion\Conductors\AssignsRoles
     */
    public function assign($roles): AssignsRoles
    {
        return new AssignsRoles($this->store(), \is_array($roles) ? $roles : \func_get_args());
    }

    /**
     * Start a chain to remove permissions from authorities.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $authorities
     *
     * @return \Ethereal\Bastion\Conductors\RemovesPermissions
     */
    public function disallow($authorities): RemovesPermissions
    {
        return new RemovesPermissions($this->store(), \is_array($authorities) ? $authorities : \func_get_args());
    }

    /**
     * Bastion instance for specific guard.
     *
     * @param string $guard
     *
     * @return \Ethereal\Bastion\Bastion
     */
    public function forGuard($guard): Bastion
    {
        return \tap(new static($this->container, $this->cache), function (Bastion $instance) use ($guard) {
            $instance->setGuard($guard);
        });
    }

    /**
     * Start a chain to forbid permissions for authority.
     *
     * @param array|\Illuminate\Database\Eloquent\Model $authorities
     *
     * @return \Ethereal\Bastion\Conductors\ForbidsPermissions
     */
    public function forbid($authorities): ForbidsPermissions
    {
        return new ForbidsPermissions($this->store(), \is_array($authorities) ? $authorities : \func_get_args());
    }

    /**
     * Return currently used guard.
     *
     * @return string
     */
    public function guard(): string
    {
        return $this->guard;
    }

    /**
     * Start a chain to check role of authority.
     *
     * @param \Illuminate\Database\Eloquent\Model $authority
     *
     * @return \Ethereal\Bastion\Conductors\ChecksRoles
     */
    public function is(Model $authority): ChecksRoles
    {
        return new ChecksRoles($this->store(), $authority);
    }

    /**
     * Start a chain to permit permissions for authority.
     *
     * @param array|\Illuminate\Database\Eloquent\Model $authorities
     *
     * @return \Ethereal\Bastion\Conductors\PermitsPermissions
     */
    public function permit($authorities): PermitsPermissions
    {
        return new PermitsPermissions($this->store(), \is_array($authorities) ? $authorities : \func_get_args());
    }

    /**
     * Start a chain to remove the given role from authority.
     *
     * @param array|string|\Illuminate\Database\Eloquent\Model $roles
     *
     * @return \Ethereal\Bastion\Conductors\RemovesRoles
     */
    public function retract($roles): RemovesRoles
    {
        return new RemovesRoles($this->store(), \is_array($roles) ? $roles : \func_get_args());
    }

    /**
     * Get or initiate a new Rucks instance.
     *
     * @param string|null $guard
     *
     * @return \Ethereal\Bastion\Rucks
     */
    public function rucks($guard = null): Rucks
    {
        $guard = $guard ?: $this->guard;

        if (!isset($this->rucks[$guard])) {
            $this->rucks[$guard] = new Rucks($this->container, $this->store());
        }

        return $this->rucks[$guard];
    }

    /**
     * Set guard.
     *
     * @param string $guard
     */
    public function setGuard($guard)
    {
        $this->guard = $guard;
    }

    /**
     * Get store.
     *
     * @return \Ethereal\Bastion\Store
     */
    public function store(): Store
    {
        return \tap(new Store(), function (Store $store) {
            $store->setCache($this->cache);
        });
    }
}
