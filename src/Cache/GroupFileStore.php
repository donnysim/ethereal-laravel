<?php

namespace Ethereal\Cache;

use Illuminate\Cache\FileStore;

class GroupFileStore extends FileStore
{
    /**
     * Get the full path for the given cache key.
     *
     * @param string $key
     *
     * @return string
     */
    protected function path($key)
    {
        $group = explode('|', $key, 2);
        $prefix = '';
        if (count($group) === 2) {
            list($prefix, $key) = $group;
        }

        $hash = sha1($key);
        $parts = array_slice(str_split($hash, 2), 0, 2);

        return "{$this->directory}/{$prefix}/" . implode('/', $parts) . "/{$hash}";
    }
}
