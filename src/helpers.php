<?php

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
        return !valid($value, $rules);
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
            if (Str::contains($field, '*')) {
                $segments = explode('.', $field);
                $anyIndex = array_search('*', $segments, true);

                $segment = implode('.', array_slice($segments, 0, $anyIndex));
                $setSegment = implode('.', array_slice($segments, 0, $anyIndex + 1));

                if ($segment === '') {
                    // The field starts with an asterisk, e.g. *.id
                    $result = [];

                    foreach (array_values($data) as $index => $value) {
                        $intersection = data_intersect($value, [implode('.', array_slice($segments, $anyIndex + 1))]);

                        if (empty($intersection)) {
                            continue;
                        }

                        Arr::set($result, str_replace('*', $index, $setSegment), $intersection);
                    }

                    $safe = array_replace_recursive($safe, $result);
                } else {
                    $result = data_intersect(Arr::get($data, $segment), [implode('.', array_slice($segments, $anyIndex))]);

                    if (!empty($result)) {
                        $tmp = [];
                        Arr::set($tmp, $segment, $result);
                        $safe = array_replace_recursive($safe, $tmp);
                    }
                }
            } else {
                if (Arr::has($data, $field)) {
                    Arr::set($safe, $field, Arr::get($data, $field));
                }
            }
        }

        return $safe;
    }
}

if (!function_exists('data_pluck')) {
    /**
     * Pluck values using dot notation.
     *
     * @param string $data
     * @param string $key
     *
     * @return array
     */
    function data_pluck($data, $key)
    {
        $safe = [];

        if (!$data) {
            return $safe;
        }

        if (Str::contains($key, '*')) {
            $segments = explode('.', $key);
            $anyIndex = array_search('*', $segments, true);

            $segment = implode('.', array_slice($segments, 0, $anyIndex));

            if ($segment === '') {
                // The field starts with an asterisk, e.g. *.id
                $result = [];

                foreach (array_values($data) as $index => $value) {
                    $plucked = data_pluck($value, implode('.', array_slice($segments, $anyIndex + 1)));

                    if (empty($plucked)) {
                        continue;
                    }

                    $safe = array_merge($safe, $plucked);
                }

                $safe = array_replace_recursive($safe, $result);
            } else {
                $result = data_pluck(Arr::get($data, $segment), implode('.', array_slice($segments, $anyIndex)));

                if (!empty($result)) {
                    $safe = array_merge($safe, $result);
                }
            }
        } else {
            if (Arr::has($data, $key)) {
                $safe[] = $data[$key];
            }
        }

        return $safe;
    }
}

if (!function_exists('model_key')) {
    /**
     * Get model key.
     *
     * @param \Illuminate\Database\Eloquent\Model|int $model
     * @param bool $throw Throw exception if model does not exist or is invalid value.
     *
     * @return number|null
     */
    function model_key($model, $throw = true)
    {
        if ($model instanceof Model) {
            if ($throw && !$model->exists) {
                throw new ModelNotFoundException('Model does not exist.');
            }

            return $model->getKey();
        }

        if (!is_numeric($model)) {
            if ($throw) {
                throw new InvalidArgumentException('Invalid argument supplied, model must be int or Model.');
            }

            return null;
        }

        return $model;
    }
}

if (!function_exists('pagination_data')) {
    /**
     * Get main data from pagination.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    function pagination_data($data)
    {
        if ($data instanceof Arrayable && $data instanceof AbstractPaginator) {
            $pagination = $data->toArray();

            return $pagination['data'];
        }

        return $data['data'];
    }
}

if (!function_exists('pagination_meta')) {
    /**
     * Get pagination meta data like page, total, etc..
     *
     * @param mixed $data
     *
     * @return mixed
     */
    function pagination_meta($data)
    {
        if ($data instanceof Arrayable && $data instanceof AbstractPaginator) {
            return Arr::except($data->toArray(), 'data');
        }

        return Arr::except($data, 'data');
    }
}

if (!function_exists('is_paginated')) {
    /**
     * Check if data is paginated.
     *
     * @param mixed $data
     *
     * @return bool
     */
    function is_paginated($data)
    {
        return $data !== null && (($data instanceof Arrayable && $data instanceof AbstractPaginator) || (is_array($data) && isset($data['current_page'])));
    }
}
