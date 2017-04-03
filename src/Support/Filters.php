<?php

namespace Ethereal\Support;

use Illuminate\Http\Request;

class Filters
{
    const ORDER_NONE = '';
    const ORDER_ASC = 'asc';
    const ORDER_DESC = 'desc';

    /**
     * Get filterable fields with their value from input.
     *
     * @param mixed $fields
     * @param array $rules
     * @param string $prefix
     *
     * @return \Generator
     */
    public static function byRules($fields, array $rules, $prefix = '')
    {
        $input = $fields;

        if ($fields instanceof Request) {
            $input = $fields->query();
        }

        $prefixLength = strlen($prefix);

        foreach ($input as $field => $value) {
            if (!empty($prefix) && strpos($field, $prefix) !== 0) {
                continue;
            }

            $ruleName = mb_strtolower(substr($field, $prefixLength));

            // Skip fields without rules.
            if (!isset($rules[$ruleName])) {
                continue;
            }

            $rule = 'filled';
            if (trim($rules[$ruleName]) !== '') {
                $rule = $rules[$ruleName];
            }

            if (valid($value, $rule)) {
                yield $ruleName => $value;
            }
        }
    }

    /**
     * Get fields that are orderable.
     *
     * @param mixed $fields
     * @param array $orderable
     * @param string $prefix
     *
     * @return \Generator
     */
    public static function orderable($fields, array $orderable, $prefix = '')
    {
        $input = $fields;

        if ($fields instanceof Request) {
            $input = $fields->query();
        }

        $prefixLength = strlen($prefix);

        foreach ($input as $field => $value) {
            if (!empty($prefix) && strpos($field, $prefix) !== 0) {
                continue;
            }

            $column = mb_strtolower(substr($field, $prefixLength));
            if (!in_array($column, $orderable, true)) {
                continue;
            }

            if (valid($value, 'in:asc,desc')) {
                yield $column => $value;
            }
        }
    }
}
