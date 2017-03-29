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
    function valid($value, $rules)
    {
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
    function invalid($value, $rules)
    {
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
    function data_intersect($data, $keys)
    {
        $safe = [];

        if (!$data) {
            return $safe;
        }

        foreach ($keys as $field) {
            if (\Illuminate\Support\Str::contains($field, '*')) {
                $parts = explode('.', $field);
                $segment = implode('.', array_slice($parts, 0, array_search('*', $parts, true)));
                $setSegment = implode('.', array_slice($parts, 0, array_search('*', $parts, true) + 1));

                if ($segment === '') {
                    // The field starts with an asterisk
                    $result = [];

                    foreach (array_values($data) as $index => $value) {
                        $intersection = data_intersect($value, [implode('.', array_slice($parts, array_search('*', $parts, true) + 1))]);

                        if (empty($intersection)) {
                            continue;
                        }

                        \Illuminate\Support\Arr::set($result, str_replace('*', $index, $setSegment), $intersection);
                    }

                    $safe = array_replace_recursive($safe, $result);
                } else {
                    $result = data_intersect(\Illuminate\Support\Arr::get($data, $segment), [implode('.', array_slice($parts, array_search('*', $parts, true)))]);

                    if (!empty($result)) {
                        $tmp = [];
                        \Illuminate\Support\Arr::set($tmp, $segment, $result);
                        $safe = array_replace_recursive($safe, $tmp);
                    }
                }
            } else {
                if (\Illuminate\Support\Arr::has($data, $field)) {
                    \Illuminate\Support\Arr::set($safe, $field, \Illuminate\Support\Arr::get($data, $field));
                }
            }
        }

        return $safe;
    }
}