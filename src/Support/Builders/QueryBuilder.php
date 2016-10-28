<?php

namespace Ethereal\Support\Builders;

use Illuminate\Support\Str;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\SoftDeletes
 */
class QueryBuilder
{
    /**
     * Query builder.
     *
     * @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Model
     */
    protected $query;

    /**
     * Parent controller.
     *
     * @var mixed
     */
    protected $controller;

    /**
     * QueryBuilder constructor.
     *
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Model $query
     * @param mixed $controller
     */
    public function __construct($query, $controller)
    {
        $this->query = $query;
        $this->controller = $controller;
    }

    /**
     * Store first result into controller.
     *
     * @param string $name
     *
     * @return $this
     */
    public function firstAs($name)
    {
        $this->controller[$name] = $this->query->first();

        return $this;
    }

    /**
     * Store firstOrCreate result into controller.
     *
     * @param string $name
     * @param array $attributes
     *
     * @return $this
     */
    public function firstOrCreateAs($name, array $attributes)
    {
        $this->controller[$name] = $this->query->firstOrCreate($attributes);

        return $this;
    }

    /**
     * Store firstOrNew result into controller.
     *
     * @param string $name
     * @param array $attributes
     *
     * @return $this
     */
    public function firstOrNewAs($name, array $attributes)
    {
        $this->controller[$name] = $this->query->firstOrNew($attributes);

        return $this;
    }

    /**
     * Store firstOrNew result into controller.
     *
     * @param string $name
     * @param array $columns
     *
     * @return $this
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function firstOrFailAs($name, array $columns = ['*'])
    {
        $this->controller[$name] = $this->query->firstOrFail($columns);

        return $this;
    }

    /**
     * Store get results into controller.
     *
     * @param string $name
     * @param array $columns
     *
     * @return $this
     */
    public function getAs($name, array $columns = ['*'])
    {
        $this->controller[$name] = $this->query->get($columns);

        return $this;
    }

    /**
     * Store paginated result into controller.
     *
     * @param string $name
     * @param null|int $perPage
     * @param array $columns
     * @param string $pageName
     * @param null|int $page
     *
     * @return $this
     */
    public function paginateAs($name, $perPage = null, array $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->controller[$name] = $this->query->paginate($perPage, $columns, $pageName, $page);

        return $this;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $prefix
     * @param $rules
     * @param null|array|bool $tableMap
     * @param bool $useOr
     *
     * @return $this
     */
    public function filterByRequest($request, $prefix, $rules, $tableMap = null, $useOr = false)
    {
        $input = $request->input();

        if (is_bool($tableMap)) {
            $useOr = $tableMap;
            $tableMap = null;
        }

        if (empty($input)) {
            return $this;
        }

        $search = [];
        $prefixLength = strlen($prefix);

        foreach ($input as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $column = substr($key, $prefixLength);

                if (isset($rules[$column])) {
                    $search[substr($key, $prefixLength)] = $value;
                }
            }
        }

        if (empty($search)) {
            return $this;
        }

        foreach ($search as $column => $value) {
            if (trim($value) === '') {
                continue;
            }

            $column = $this->getFullColumn($tableMap, mb_strtolower($column));
            $type = $rules[$column];

            if (is_array($type)) {
                call_user_func($type, $value);
            } else {
                $type = mb_strtolower($type);

                if (Str::contains($type, ':value')) {
                    // if type contains :value, we assume it's LIKE, it allows specifying
                    // exact format like %:value%
                    if ($useOr) {
                        $this->query->orWhere($column, 'LIKE', str_replace(':value', $value, $type));
                    } else {
                        $this->query->where($column, 'LIKE', str_replace(':value', $value, $type));
                    }
                } else {
                    // if most likely an equation
                    if ($useOr) {
                        $this->query->orWhere($column, $type, $value);
                    } else {
                        $this->query->where($column, $type, $value);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Apply order by to query from provided request.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $field
     * @param null|array $tableMap
     * @param bool|array $onlyFields
     *
     * @return $this
     */
    public function orderByRequest($request, $field, $tableMap = null, $onlyFields = false)
    {
        // We expect it to be a request object and get order by values from request in a form of ?field[]=
        $orderValues = $request->get($field, []);

        if (empty($orderValues)) {
            return $this;
        }

        foreach ($orderValues as $key => $column) {
            list($column, $direction) = $this->getColumnAndOrder($key, $column);
            $column = $this->getFullColumn($tableMap, $column);

            if ($onlyFields === false || ($onlyFields && in_array($column, $onlyFields, true))) {
                $this->query->orderBy($column, $direction);
            }
        }

        return $this;
    }

    /**
     * Get full column name with table if provided in map.
     *
     * @param null|array $map
     * @param string $column
     *
     * @return string
     */
    private function getFullColumn($map, $column)
    {
        if (empty($map)) {
            return $column;
        }

        if (isset($map[$column])) {
            return "{$map[$column]}.{$column}";
        }

        if (isset($map['*'])) {
            return "{$map['*']}.{$column}";
        }

        return $column;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return array
     */
    private function getColumnAndOrder($key, $value)
    {
        // if key is numeric, we assume value is in format - "name", "-name"
        if (is_numeric($key)) {
            $direction = 'asc';

            if (Str::startsWith($value, '-')) {
                $direction = 'desc';
                $value = substr($value, 1);
            }

            return [$value, $direction];
        }

        return [$key, $value];
    }

    /**
     * is triggered when invoking inaccessible methods in an object context.
     *
     * @param $name string
     * @param $arguments array
     *
     * @return mixed
     * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.methods
     */
    public function __call($name, $arguments)
    {
        call_user_func_array([$this->query, $name], $arguments);

        return $this;
    }
}
