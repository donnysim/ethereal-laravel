<?php

namespace Ethereal\Bastion;

use Illuminate\Contracts\Container\Container;

class Bastion
{
    /**
     * Default rucks type to use.
     *
     * @var string
     */
    protected static $type = 'user';

    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Initiated ruck instances.
     *
     * @var array Type => Rucks
     */
    protected $rucks = [];

    /**
     * Create a new rucks instance.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get or initiate a new Rucks instance.
     *
     * @param string|null $type
     *
     * @return \Ethereal\Bastion\Rucks
     */
    public function rucks($type = null)
    {
        if (!$type) {
            $type = static::$type;
        }

        if (!isset($this->rucks[$type])) {
            $this->rucks[$type] = new Rucks($this->container);
        }

        return $this->rucks[$type];
    }

    /**
     * Set default rucks type.
     *
     * @param string $type
     */
    public function useType($type)
    {
        static::$type = $type;
    }
}
