<?php

namespace Ethereal\Database\Scopes;

trait QueryModifiers
{
    /**
     * Apply query modifiers.
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param array|string $modifiers
     *
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public function scopeModifiers($query, $modifiers)
    {
        if (!is_array($modifiers)) {
            $modifiers = array_slice(func_get_args(), 1);
        }

        foreach ($modifiers as $modifier) {
            $instance = $modifier;

            if (is_string($modifier)) {
                $instance = app($modifier);
            }

            $instance->apply($query);
        }

        return $query;
    }
}
