<?php

namespace Ethereal\Support\Builders;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Model|\Ethereal\Database\Ethereal|\Illuminate\Database\Eloquent\SoftDeletes
 */
class ModelBuilder
{
    /**
     * Query builder.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Parent controller.
     *
     * @var mixed
     */
    protected $controller;

    /**
     * QueryBuilder constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param mixed $controller
     */
    public function __construct($model, $controller)
    {
        $this->model = $model;
        $this->controller = $controller;
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
        call_user_func_array([$this->model, $name], $arguments);

        return $this;
    }
}
