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
