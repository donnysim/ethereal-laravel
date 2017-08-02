<?php

namespace Ethereal\Bastion;

use Ethereal\Bastion\Policy\PolicyResult;
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
        if (is_callable($callback)) {
            $this->abilities[$ability] = $callback;
        } elseif (is_string($callback) && Str::contains($callback, '@')) {
            $this->abilities[$ability] = $this->buildAbilityCallback($callback);
        } else {
            throw new InvalidArgumentException("Callback must be a callable or a 'Class@method' string.");
        }

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
     *
     * @return $this
     */
    public function policy($model, $policy)
    {
        $this->policies[$model] = $policy;

        return $this;
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
     * @param string $class
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

        if (!is_string($class)) {
            return null;
        }

        if (isset($this->policies[$class])) {
            return $this->resolvePolicy($this->policies[$class]);
        }

        foreach ($this->policies as $expected => $policy) {
            if (is_subclass_of($class, $expected)) {
                return $this->resolvePolicy($policy);
            }
        }

        return null;
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
     * @param string $class
     *
     * @return mixed
     */
    public function resolvePolicy($class)
    {
        return $this->container->make($class);
    }

    /**
     * Determine if the ability is allowed for the current user.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null|array $model
     * @param array $payload
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function allows($ability, $model = null, array $payload = [])
    {
        return $this->check($ability, $model, $payload)->allowed();
    }

    /**
     * Determine if the ability is denied for the current user.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null|array $model
     * @param array $payload
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function denies($ability, $model = null, array $payload = [])
    {
        return $this->check($ability, $model, $payload)->denied();
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null|array $model
     * @param array $payload
     *
     * @return \Ethereal\Bastion\Policy\PolicyResult
     * @throws \InvalidArgumentException
     */
    public function check($ability, $model = null, array $payload = [])
    {
        $user = $this->resolveUser();

        if (!$user) {
            return new PolicyResult(false, 'User is not authenticated.');
        }

        $args = $this->resolveArgs($ability, $model, $payload);
        $policyResult = null;

        // Check before callbacks
        $result = $this->callCallbacks($this->beforeCallbacks, $user, $args);
        if ($result !== null) {
            $policyResult = PolicyResult::fromResult($result,
                PolicyResult::accessGranted($result)
                    ? 'Allowed by one of the before callbacks.'
                    : 'Denied by one of the before callbacks.'
            );
        }

        // Check main callbacks
        if (!$policyResult) {
            $result = $this->callAuthCallback($user, $args);
            if ($result !== null) {
                $policyResult = PolicyResult::fromResult($result, '');
            }
        }

        if (!$policyResult) {
            $policyResult = new PolicyResult(false, 'Access denied because no policies could determine the outcome.');
        }

        $this->callCallbacks($this->afterCallbacks, $user, $args, [$policyResult]);
        return $policyResult;
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
     * @param array|null $payload
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
    protected function callCallbacks(array $callbacks, $user, Args $args, array $payload = [])
    {
        $grant = null;

        foreach ($callbacks as $callback) {
            $result = $callback(...array_merge([$user, $args], $payload));

            if (PolicyResult::accessDenied($result)) {
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
            return PolicyResult::fromResult(false, 'No auth callbacks defined.');
        }

        $result = $callback(...array_merge([$user], $args->arguments()));
        if ($result !== null && !$result instanceof PolicyResult) {
            return PolicyResult::fromResult($result, $args->ability() . ' ability ' . (PolicyResult::accessGranted($result) ? 'granted' : 'denied') . ' access.');
        }

        return $result;
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
            $policyResult = null;

            if (method_exists($instance, 'before')) {
                $result = call_user_func_array([$instance, 'before'], [$user, $args]);

                if ($result !== null) {
                    $granted = PolicyResult::accessGranted($result);
                    $callable = get_class($instance) . '@before';

                    $policyResult = PolicyResult::fromResult($result, $callable . ($granted ? ' granted access.' : ' denied access.'));
                }
            }

            if ($policyResult === null && !is_callable([$instance, $args->method()])) {
                $callable = get_class($instance) . '@' . $args->method();
                $policyResult = new PolicyResult(false, 'Access denied because method ' . $callable . ' does not exist.');
            }

            if ($policyResult === null) {
                $result = $instance->{$args->method()}(...array_merge([$user], $args->arguments()));
                if ($result !== null) {
                    $granted = PolicyResult::accessGranted($result) ? 'granted' : 'denied';
                    $callable = get_class($instance) . '@' . $args->method();
                    $policyResult = new PolicyResult($result, "{$callable} {$granted} access.");
                }
            }

            if (method_exists($instance, 'after')) {
                call_user_func_array([$instance, 'after'], [$user, $args, $policyResult]);
            }

            return $policyResult;
        };
    }
}
