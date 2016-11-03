<?php

namespace Bastion;

use Ethereal\Bastion\Helper;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Rucks
{
    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The user resolver callable.
     *
     * @var callable
     */
    protected $userResolver;

    /**
     * All of the defined abilities.
     *
     * @var array
     */
    protected $abilities = [];

    /**
     * All of the defined policies.
     *
     * @var array
     */
    protected $policies = [];

    /**
     * All of the registered before callbacks.
     *
     * @var array
     */
    protected $beforeCallbacks = [];

    /**
     * All of the registered after callbacks.
     *
     * @var array
     */
    protected $afterCallbacks = [];

    /**
     * Create a new gate instance.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @param callable $userResolver
     * @param callable $guestResolver
     * @param array $abilities
     * @param array $guestAbilities
     * @param array $policies
     * @param array $beforeCallbacks
     * @param array $afterCallbacks
     */
    public function __construct(
        Container $container,
        callable $userResolver,
        array $abilities = [],
        array $policies = [],
        array $beforeCallbacks = [],
        array $afterCallbacks = [])
    {
        $this->policies = $policies;
        $this->container = $container;
        $this->abilities = $abilities;
        $this->userResolver = $userResolver;
        $this->afterCallbacks = $afterCallbacks;
        $this->beforeCallbacks = $beforeCallbacks;
    }

    /**
     * Determine if a given ability has been defined.
     *
     * @param string $ability
     *
     * @return bool
     */
    public function has($ability)
    {
        return isset($this->abilities[$ability]);
    }

    /**
     * Register a callback to run before all Gate checks.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function before(callable $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to run after all Gate checks.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function after(callable $callback)
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    /**
     * Define a new ability.
     *
     * @param string $ability
     * @param callable|string $callback
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function define($ability, $callback)
    {
        if (is_callable($callback)) {
            // Already a callable
        } elseif (is_string($callback) && Str::contains($callback, '@')) {
            $callback = $this->buildAbilityCallback($callback);
        } else {
            throw new InvalidArgumentException("Callback must be a callable or a 'Class@method' string.");
        }

        $this->abilities[$ability] = $callback;

        return $this;
    }

    /**
     * Define a policy class for a given class type.
     *
     * @param string $class
     * @param string $policy
     *
     * @return $this
     */
    public function policy($class, $policy)
    {
        $this->policies[$class] = $policy;

        return $this;
    }

    /**
     * Create the ability callback for a callback string.
     *
     * @param string $callback
     *
     * @return \Closure
     */
    protected function buildAbilityCallback($callback)
    {
        return function () use ($callback) {
            list($class, $method) = explode('@', $callback);

            return call_user_func_array([$this->resolvePolicy($class), $method], func_get_args());
        };
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param string $ability
     * @param array|mixed $arguments
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function allows($ability, $arguments = [])
    {
        return $this->check($ability, $arguments);
    }

    /**
     * Determine if the given ability should be denied for the current user.
     *
     * @param string $ability
     * @param array|mixed $arguments
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function denies($ability, $arguments = [])
    {
        return !$this->allows($ability, $arguments);
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param string $ability
     * @param array|mixed $arguments
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function check($ability, $arguments = [])
    {
        $isGuest = false;
        $user = $this->resolveUser();

        if ($user === null) {
            $isGuest = true;
            $user = $this->resolveGuest();
        }
    }

    /**
     * Check if policy has a handler defined for checking ability.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string $model
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function hasPolicyCheck($ability, $model)
    {
        list($method) = $this->resolveParams($ability, $model, null);

        $class = $model;

        if (is_object($class)) {
            $class = get_class($class);
        }

        $instance = $this->getPolicyFor($class, false);

        if ($instance === null) {
            return false;
        }

        if (method_exists($instance, $method)) {
            return true;
        }

        return false;
    }

    /**
     * Get a policy instance for a given class.
     *
     * @param object|string $class
     * @param bool $throw
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getPolicyFor($class, $throw = true)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (!isset($this->policies[$class])) {
            if ($throw) {
                throw new InvalidArgumentException("Policy not defined for [{$class}].");
            }

            return null;
        }

        return $this->resolvePolicy($this->policies[$class]);
    }

    /**
     * Build a policy class instance of the given type.
     *
     * @param object|string $class
     *
     * @return mixed
     */
    public function resolvePolicy($class)
    {
        return $this->container->make($class);
    }

    /**
     * Get a guard instance for the given user.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|mixed $user
     *
     * @return static
     */
    public function forUser($user)
    {
        $callback = function () use ($user) {
            return $user;
        };

        return new static(
            $this->container, $callback, $this->abilities,
            $this->policies, $this->beforeCallbacks, $this->afterCallbacks
        );
    }

    /**
     * Resolve the user from the user resolver.
     *
     * @return mixed
     */
    protected function resolveUser()
    {
        return call_user_func($this->userResolver);
    }

    /**
     * Resolve request params.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string $model
     * @param int|null $key
     *
     * @return array
     */
    protected function resolveParams($ability, $model, $key)
    {
        $modelKey = $key;

        if (strpos($ability, '-') !== false) {
            $ability = Str::camel($ability);
        }

        if (is_string($model)) {
            $modelName = Helper::getMorphClassName($model);
        } else {
            $modelName = $model->getMorphClass();
            $modelKey = $model->getKey();
        }

        return [$ability, $modelName, $modelKey];
    }
}
