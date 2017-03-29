<?php

if (!function_exists('valid')) {
    /**
     * Determine if the given value passes given rules.
     *
     * @param mixed $value
     * @param string|array $rules
     *
     * @return bool
     */
    function valid($value, $rules) {
        return validator(['value' => $value], ['value' => $rules])->fails() === false;
    }
}

if (!function_exists('invalid')) {
    /**
     * Determine if the given value is fails to pass given rules.
     *
     * @param mixed $value
     * @param string|array $rules
     *
     * @return bool
     */
    function invalid($value, $rules) {
        return validator(['value' => $value], ['value' => $rules])->fails();
    }
}

if (!function_exists('data_intersect')) {
    /**
     * Get data that intersects using dot notation.
     *
     * @param array $data
     * @param array $keys
     *
     * @return array
     */
    function data_intersect($data, $keys) {
        $safe = [];

        foreach ($keys as $field) {
            if (\Illuminate\Support\Arr::has($data, $field)) {
                \Illuminate\Support\Arr::set($safe, $field, \Illuminate\Support\Arr::get($data, $field));
            }
        }

        return $safe;
    }
}