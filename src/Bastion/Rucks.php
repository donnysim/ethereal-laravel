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
     * Primary store used to get roles and abilities.
     *
     * @var \Ethereal\Bastion\Store
     */
    protected $store;

    /**
     * Create a new rucks instance.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @param \Ethereal\Bastion\Store $store
     */
    public function __construct(Container $container, $store)
    {
        $this->container = $container;
        $this->store = $store;
    }

    /**
     * Define a new ability.
     *
     * @param string $ability
     * @param callable $callback
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
     * Determine if the ability is defined.
     *
     * @param string $ability
     *
     * @return bool
     */
    public function hasAbility($ability)
    {
        return isset($this->abilities[$ability]);
    }

    /**
     * Register a policy for model.
     *
     * @param string $model
     * @param string $policy
     */
    public function policy($model, $policy)
    {
        $this->policies[$model] = $policy;
    }

    /**
     * Get registered policies.
     *
     * @return array
     */
    public function policies()
    {
        return $this->policies;
    }

    /**
     * Determine if the policy is defined.
     *
     * @param string $policy
     *
     * @return bool
     */
    public function hasPolicy($policy)
    {
        return isset($this->policies[$policy]);
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
     * Get store.
     *
     * @return \Ethereal\Bastion\Store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Set store.
     *
     * @param \Ethereal\Bastion\Store $store
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * Set new user resolver without creating new instance of Rucks.
     *
     * @param callable $resolver
     */
    public function setUserResolver(callable $resolver)
    {
        $this->userResolver = $resolver;
    }

    /**
     * Resolve the user from user resolver.
     *
     * @return mixed
     */
    public function resolveUser()
    {
        return call_user_func($this->userResolver);
    }

    /**
     * Create a new instance of rucks for specific user.
     *
     * @param mixed $user
     *
     * @return static
     */
    public function forUser($user)
    {
        $rucks = new static($this->container, $this->getStore());
        $rucks->setUserResolver(function () use ($user) {
            return $user;
        });

        return $rucks;
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
        $instance = $this->getPolicyFor($args->modelClass(), false);

        if ($instance === null) {
            return false;
        }

        if (method_exists($instance, $args->method())) {
            return true;
        }

        return false;
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

        return (bool)$result;
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
     * Resolve request params.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|array|null $model
     * @param array $payload
     *
     * @return \Ethereal\Bastion\Args
     */
    protected function resolveArgs($ability, $model, $payload = [])
    {
        return new Args($ability, $model, $payload);
    }

    /**
     * Determine if arguments correspond to a policy.
     *
     * @param \Ethereal\Bastion\Args $args
     *
     * @return bool
     */
    protected function correspondsToPolicy(Args $args)
    {
        if ($args->modelClass() === null) {
            return false;
        }

        return $this->hasPolicy($args->modelClass());
    }

    /**
     * Call all of the before callbacks and return if result is given.
     *
     * @param array $callbacks
     * @param \Illuminate\Database\Eloquent\Model $user
     * @param \Ethereal\Bastion\Args $args
     * @param array $payload
     *
     * @return bool|null
     */
    protected function callCallbacks(array $callbacks, $user, Args $args, $payload = [])
    {
        $grant = null;

        foreach ($callbacks as $callback) {
            $result = $callback(...array_merge([$user, $args], $payload));

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
     * @param \Ethereal\Bastion\Args $args
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function callAuthCallback($user, Args $args)
    {
        $callback = null;

        if ($this->correspondsToPolicy($args)) {
            $callback = $this->resolvePolicyCallback($user, $args);
        } elseif ($this->hasAbility($args->ability())) {
            $callback = $this->abilities[$args->ability()];
        } else {
            $callback = function () {
                return false;
            };
        }

        return $callback(...array_merge([$user], $args->arguments()));
    }

    /**
     * Resolve the callback for a policy check.
     *
     * @param \Illuminate\Database\Eloquent\Model $user
     * @param \Ethereal\Bastion\Args $args
     *
     * @return \Closure
     * @throws \InvalidArgumentException
     */
    protected function resolvePolicyCallback($user, Args $args)
    {
        return function () use ($user, $args) {
            $instance = $this->getPolicyFor($args->modelClass());

            if (method_exists($instance, 'before')) {
                $result = call_user_func_array([$instance, 'before'], [$user, $args]);

                if ($result !== null) {
                    return $result;
                }
            }

            if (!is_callable([$instance, $args->method()])) {
                return false;
            }

            $result = $instance->{$args->method()}(...array_merge([$user], $args->arguments()));

            if (method_exists($instance, 'after')) {
                call_user_func_array([$instance, 'after'], [$user, $args, $result]);
            }

            return $result;
        };
    }
}
