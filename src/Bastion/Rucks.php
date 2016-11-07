<?php

namespace Ethereal\Bastion;

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
     * Create a new rucks instance.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @param callable $userResolver
     * @param array $abilities
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
     * Register a callback to run before all checks.
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
     * Register a callback to run after all checks.
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
        if (is_string($callback) && Str::contains($callback, '@')) {
            $callback = $this->buildAbilityCallback($callback);
        } elseif (!is_callable($callback)) {
            throw new InvalidArgumentException("Callback must be a callable or a 'Class@method' string.");
        }

        $this->abilities[$ability] = $callback;

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
     * Determine if the given ability should be denied for the current user.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param array $payload
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function denies($ability, $model = null, $payload = [])
    {
        return !$this->allows($ability, $model, $payload);
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param array $payload
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function allows($ability, $model = null, $payload = [])
    {
        return $this->check($ability, $model, $payload);
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null|array $model
     * @param array $payload
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function check($ability, $model = null, $payload = [])
    {
        $user = $this->resolveUser();

        if (!$user) {
            return false;
        }

        $args = $this->resolveArgs($ability, $model, $payload);

        $result = $this->callCallbacks($this->beforeCallbacks, $user, $args);
        if ($result === null) {
            $result = $this->callAuthCallback($user, $args);
        }

        $this->callCallbacks($this->afterCallbacks, $user, $args, [$result]);

        return $result;
    }

    /**
     * Resolve the user from the user resolver.
     *
     * @return mixed
     */
    public function resolveUser()
    {
        return call_user_func($this->userResolver);
    }

    /**
     * Resolve request params.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|array|null $model
     * @param array $payload
     *
     * @return \Ethereal\Bastion\RuckArgs
     */
    protected function resolveArgs($ability, $model, $payload = [])
    {
        return new RuckArgs($ability, $model, $payload);
    }

    /**
     * Call all of the before callbacks and return if result is given.
     *
     * @param array $callbacks
     * @param \Illuminate\Database\Eloquent\Model $user
     * @param \Ethereal\Bastion\RuckArgs $args
     * @param array $payload
     *
     * @return mixed|null
     */
    protected function callCallbacks(array $callbacks, $user, RuckArgs $args, $payload = [])
    {
        $grant = null;

        foreach ($callbacks as $callback) {
            $result = call_user_func_array($callback, array_merge([$user, $args], $payload));

            if ($result === false) {
                return $result;
            } elseif ($result !== null) {
                $grant = $result;
            }
        }

        return $grant;
    }

    /**
     * Call auth user callback.
     *
     * @param \Illuminate\Database\Eloquent\Model $user
     * @param \Ethereal\Bastion\RuckArgs $args
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function callAuthCallback($user, RuckArgs $args)
    {
        $callback = null;

        if ($this->correspondsToPolicy($args)) {
            $callback = $this->resolvePolicyCallback($user, $args);
        } elseif (isset($this->abilities[$args->getAbility()])) {
            $callback = $this->abilities[$args->getAbility()];
        } else {
            $callback = function () {
                return false;
            };
        }

        return call_user_func_array($callback, array_merge([$user], $args->getArguments()));
    }

    /**
     * Determine if arguments correspond to a policy.
     *
     * @param \Ethereal\Bastion\RuckArgs $args
     *
     * @return bool
     */
    protected function correspondsToPolicy(RuckArgs $args)
    {
        if ($args->getClass() === null) {
            return false;
        }

        return isset($this->policies[$args->getClass()]);
    }

    /**
     * Resolve the callback for a policy check.
     *
     * @param \Illuminate\Database\Eloquent\Model $user
     * @param \Ethereal\Bastion\RuckArgs $args
     *
     * @return \Closure
     * @throws \InvalidArgumentException
     */
    protected function resolvePolicyCallback($user, RuckArgs $args)
    {
        return function () use ($user, $args) {
            $instance = $this->getPolicyFor($args->getClass());

            if (method_exists($instance, 'before')) {
                $result = call_user_func_array([$instance, 'before'], [$user, $args]);

                if ($result !== null) {
                    return $result;
                }
            }

            if (!is_callable([$instance, $args->getMethod()])) {
                return false;
            }

            $result = call_user_func_array([$instance, $args->getMethod()], array_merge([$user], $args->getArguments()));

            if (method_exists($instance, 'after')) {
                call_user_func_array([$instance, 'after'], [$user, $args, $result]);
            }

            return $result;
        };
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
        $args = $this->resolveArgs($ability, $model, null);
        $instance = $this->getPolicyFor($args->getClass(), false);

        if ($instance === null) {
            return false;
        }

        if (method_exists($instance, $args->getMethod())) {
            return true;
        }

        return false;
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
}
