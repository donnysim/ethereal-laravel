<?php

namespace Ethereal\Bastion;

use Ethereal\Bastion\Policies\PolicyResult;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Rucks
{
    /**
     * All of the defined abilities.
     *
     * @var array
     */
    protected $abilities = [];

    /**
     * All of the registered after callbacks.
     *
     * @var array
     */
    protected $afterCallbacks = [];

    /**
     * All of the registered before callbacks.
     *
     * @var array
     */
    protected $beforeCallbacks = [];

    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * All of the defined policies.
     *
     * @var array
     */
    protected $policies = [];

    /**
     * Primary store used to get roles and abilities.
     *
     * @var \Ethereal\Bastion\Store
     */
    protected $store;

    /**
     * The user resolver callable.
     *
     * @var callable
     */
    protected $userResolver;

    /**
     * Rucks constructor.
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
     * Get all of the defined abilities.
     *
     * @return array
     */
    public function abilities(): array
    {
        return $this->abilities;
    }

    /**
     * Register a callback to run after all Gate checks.
     *
     * @param callable $callback
     *
     * @return \Ethereal\Bastion\Rucks
     */
    public function after(callable $callback): Rucks
    {
        $this->afterCallbacks[] = $callback;

        return $this;
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
    public function allows($ability, $model = null, array $payload = []): bool
    {
        return $this->check($ability, $model, $payload)->allowed();
    }

    /**
     * Register a callback to run before all Gate checks.
     *
     * @param callable $callback
     *
     * @return \Ethereal\Bastion\Rucks
     */
    public function before(callable $callback): Rucks
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null|array $model
     * @param array $payload
     *
     * @return \Ethereal\Bastion\Policies\PolicyResult
     * @throws \InvalidArgumentException
     */
    public function check($ability, $model = null, array $payload = []): PolicyResult
    {
        $user = $this->resolveUser();
        if (!$user) {
            return PolicyResult::deny('User if not authenticated.');
        }

        $args = Args::resolve($ability, $model, $payload);
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

        // Call policy or ability callback
        if (!$policyResult) {
            $result = $this->callAuthCallback($user, $args);
            if ($result !== null) {
                $policyResult = PolicyResult::fromResult($result);
            }
        }

        // Ensure that access has been determined
        if (!$policyResult) {
            $policyResult = new PolicyResult(false, 'Access denied because no policies could determine the outcome.');
        }

        $this->callCallbacks($this->afterCallbacks, $user, $args, [$policyResult]);
        return $policyResult;
    }

    /**
     * Define a new ability.
     *
     * @param string $ability
     * @param callable|string $callback
     *
     * @return \Ethereal\Bastion\Rucks
     * @throws \InvalidArgumentException
     */
    public function define($ability, $callback): Rucks
    {
        if (\is_callable($callback)) {
            $this->abilities[$ability] = $callback;
        } elseif (\is_string($callback) && Str::contains($callback, '@')) {
            $this->abilities[$ability] = $this->buildAbilityCallback($callback);
        } else {
            throw new InvalidArgumentException("Callback must be a callable or a 'Class@method' string.");
        }

        return $this;
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
    public function denies($ability, $model = null, array $payload = []): bool
    {
        return $this->check($ability, $model, $payload)->denied();
    }

    /**
     * Create a new instance of rucks for specific user.
     *
     * @param mixed $user
     *
     * @return \Ethereal\Bastion\Rucks
     */
    public function forUser($user): Rucks
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
     *
     * @return mixed
     */
    public function getPolicyFor($class)
    {
        if (\is_object($class)) {
            $class = \get_class($class);
        }

        if (!\is_string($class)) {
            return null;
        }

        if (isset($this->policies[$class])) {
            return $this->resolvePolicy($this->policies[$class]);
        }

        foreach ($this->policies as $expected => $policy) {
            if (\is_subclass_of($class, $expected)) {
                return $this->resolvePolicy($policy);
            }
        }

        return null;
    }

    /**
     * Get store.
     *
     * @return \Ethereal\Bastion\Store|null
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Determine if a given ability has been defined.
     *
     * @param string|array $ability
     *
     * @return bool
     */
    public function hasAbility($ability): bool
    {
        $abilities = \is_array($ability) ? $ability : \func_get_args();

        foreach ($abilities as $name) {
            if (!isset($this->abilities[$name])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the given policy is defined.
     *
     * @param string $policy
     *
     * @return bool
     */
    public function hasPolicy($policy): bool
    {
        return isset($this->policies[$policy]);
    }

    /**
     * Get all of the defined policies.
     *
     * @return array
     */
    public function policies(): array
    {
        return $this->policies;
    }

    /**
     * Define a policy class for a given class type.
     *
     * @param string $class
     * @param string $policy
     *
     * @return \Ethereal\Bastion\Rucks
     */
    public function policy($class, $policy): Rucks
    {
        $this->policies[$class] = $policy;

        return $this;
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
     * Resolve the user from user resolver.
     *
     * @return mixed
     */
    public function resolveUser()
    {
        return \call_user_func($this->userResolver);
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
     * Create the ability callback for a callback string.
     *
     * @param string $callback
     *
     * @return \Closure
     */
    protected function buildAbilityCallback($callback): callable
    {
        return function () use ($callback) {
            list($class, $method) = Str::parseCallback($callback);

            return $this->resolvePolicy($class)->{$method}(...\func_get_args());
        };
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

        if ($args->modelClass()) {
            $policy = $this->getPolicyFor($args->modelClass());

            if ($policy !== null) {
                $callback = $this->resolvePolicyCallback($user, $args, $policy);
                return $callback();
            }
        }

        if (isset($this->abilities[$args->ability()])) {
            return $this->abilities[$args->ability()];
        }

        return PolicyResult::fromResult(false, 'No auth callbacks defined.');;
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
            $result = $callback(...\array_merge([$user, $args], $payload));

            if (PolicyResult::accessDenied($result)) {
                return $result;
            }

            if ($result !== null) {
                $grant = $result;
            }
        }

        return $grant;
    }

    /**
     * Call policy before callback if available.
     *
     * @param mixed $policy
     * @param mixed $user
     * @param \Ethereal\Bastion\Args $args
     *
     * @return \Ethereal\Bastion\Policies\PolicyResult|null
     */
    protected function callPolicyBefore($policy, $user, $args)
    {
        if (!\method_exists($policy, 'before')) {
            return null;
        }

        $result = $policy->before($user, $args);
        if ($result === null) {
            return null;
        }

        $granted = PolicyResult::accessGranted($result);
        return PolicyResult::fromResult(
            $result,
            \get_class($policy) . '@before' . ($granted ? ' granted access.' : ' denied access.')
        );
    }

    /**
     * Resolve the callback for a policy check.
     *
     * @param \Illuminate\Database\Eloquent\Model $user
     * @param \Ethereal\Bastion\Args $args
     * @param $policy
     *
     * @return \Closure
     */
    protected function resolvePolicyCallback($user, Args $args, $policy): callable
    {
        return function () use ($policy, $user, $args) {
            $policyResult = $this->callPolicyBefore($policy, $user, $args);

            if ($policyResult !== null) {
                return $policyResult;
            }

            if (!\is_callable([$policy, $args->method()])) {
                return PolicyResult::deny(
                    'Access denied because method ' . (\get_class($policy) . '@' . $args->method()) . ' does not exist.'
                );
            }

            $result = $policy->{$args->method()}(...\array_merge([$user], $args->arguments()));
            if ($result === null) {
                return null;
            }

            $granted = PolicyResult::accessGranted($result) ? 'granted' : 'denied';
            $callable = \get_class($policy) . '@' . $args->method();
            return PolicyResult::fromResult($result, "{$callable} {$granted} access");
        };
    }
}
