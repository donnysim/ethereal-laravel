<?php

namespace Ethereal\Database\Traits;

use Illuminate\Support\Str;

trait WithoutFillable
{
    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->guarded = [];

        parent::__construct($attributes);
    }

    /**
     * Determine if the model is totally guarded.
     *
     * @return bool
     */
    public function totallyGuarded(): bool
    {
        return false;
    }

    /**
     * Determine if the given key is guarded.
     *
     * @param string $key
     *
     * @return bool
     */
    public function isGuarded($key): bool
    {
        return false;
    }

    /**
     * Determine if the given attribute may be mass assigned.
     *
     * @param string $key
     *
     * @return bool
     */
    public function isFillable($key): bool
    {
        return !Str::startsWith($key, '_');
    }

    /**
     * Get the fillable attributes of a given array.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function fillableFromArray(array $attributes): array
    {
        return $attributes;
    }
}
