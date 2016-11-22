<?php

namespace Ethereal\Bastion\Conductors\Traits;

trait ClearsCache
{
    /**
     * Clear cache for one or all authorities.
     *
     * @param \Ethereal\Bastion\Store\Store $store
     * @param bool $all
     * @param array|null $authorities
     */
    protected function clearCache($store, $all, $authorities = null)
    {
        if ($all) {
            $store->clearCache();
        } elseif ($authorities) {
            foreach ($authorities as $authority) {
                if (!is_string($authority)) {
                    $store->clearCacheFor($authority);
                }
            }
        }
    }
}
