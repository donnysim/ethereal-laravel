<?php

namespace Ethereal\Cache;

use Illuminate\Cache\FileStore;

class TagFileStore extends FileStore
{
    /**
     * Cache tags.
     *
     * @var array
     */
    protected $tags = [];

    /**
     * Set cache tags.
     *
     * @param string|array $tags
     */
    public function tags($tags)
    {
        $this->tags = (array)$tags;
    }

    /**
     * Get the full path for the given cache key.
     *
     * @param string $key
     *
     * @return string
     */
    protected function path($key)
    {
        $ds = DIRECTORY_SEPARATOR;

        $paths = [$this->directory];

        // Add tags paths
        if ($this->tags) {
            $paths[] = implode($ds, $this->tags);
        }

        $paths[] = implode($ds, array_slice(str_split(sha1($key), 2), 0, 2));
        $paths[] = sha1($key);

        return implode($ds, $paths);
    }
}
