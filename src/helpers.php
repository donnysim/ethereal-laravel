<?php

if (!function_exists('valid')) {
    /**
     * Determine if the given value is valid against given rules.
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