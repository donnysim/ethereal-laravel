<?php

namespace Ethereal\Bastion;

class Store
{
    /**
     * The tag used for caching.
     *
     * @var string
     */
    protected $tag = 'donnysim-bastion';

    /**
     * The cache store.
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $cache;

    /**
     * Use cache to store query results.
     *
     * @var bool
     */
    protected $useCache = true;
}
