<?php

namespace Ethereal\Bastion;

use Illuminate\Contracts\Container\Container;

class Rucks
{
    use Traits\ManagesPermissions;

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
}
