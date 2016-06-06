<?php

namespace Ethereal\Support;

use ArrayAccess;
use Closure;
use Ethereal\Database\Ethereal;
use Ethereal\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * @property \Ethereal\Http\JsonResponse json
 */
abstract class EtherealController extends Controller implements ArrayAccess
{
    /**
     * Attribute values.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Application request.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Controller constructor.
     *
     * @param $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder $class
     * @param \Closure $callback
     * @return $this
     */
    protected function query($class, Closure $callback)
    {
        $builder = null;

        if (is_object($class)) {
            $builder = new Builders\QueryBuilder($class, $this);
        } else {
            $builder = new Builders\QueryBuilder($class::query(), $this);
        }

        $callback($builder);

        return $this;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $class
     * @param null|int $id
     * @param \Closure $callback
     * @return $this
     */
    protected function model($class, $id = null, Closure $callback = null)
    {
        $builder = null;

        if (is_object($class)) {
            $builder = new Builders\ModelBuilder($class, $this);
        } elseif (is_numeric($id)) {
            $builder = new Builders\ModelBuilder($class::findOrFail($id), $this);
        } else {
            $builder = new Builders\ModelBuilder(new $class, $this);
        }

        if ($id instanceof Closure) {
            $id($builder);
        } elseif ($callback instanceof Closure) {
            $callback($builder);
        }

        return $this;
    }

    /**
     * Build a json response.
     *
     * @param Closure|null $callback
     * @return \Ethereal\Http\JsonResponse|$this
     */
    protected function json(Closure $callback = null)
    {
        if ($callback instanceof Closure) {
            $callback($this->json);

            return $this;
        }

        return $this->json;
    }

    /**
     * Get json response object.
     *
     * @return \Ethereal\Http\JsonResponse
     */
    private function getJsonProperty()
    {
        return $this->properties['json'] = new JsonResponse();
    }

    /**
     * Authorize user action.
     *
     * @param string $action
     * @param mixed $target
     * @param array $params
     * @return $this
     */
    abstract protected function authorize($action, $target = null, array $params = []);

    /**
     * is utilized for reading data from inaccessible members.
     *
     * @param $name string
     * @return mixed
     * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }

        $methodName = 'get' . Str::studly($name) . 'Property';
        if (method_exists($this, $methodName)) {
            return $this->{$methodName}();
        }
    }

    /**
     * run when writing data to inaccessible members.
     *
     * @param $name string
     * @param $value mixed
     * @return void
     * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __set($name, $value)
    {
        $methodName = 'set' . Str::studly($name) . 'Property';
        if (method_exists($this, $methodName)) {
            $this->{$methodName}($value);
        } else {
            $this->properties[$name] = $value;
        }
    }

    /**
     * is invoked when unset() is used on inaccessible members.
     *
     * @param $name string
     * @return void
     * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __unset($name)
    {
        unset($this->properties[$name]);
    }

    /**
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->properties);
    }

    /**
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        if (! array_key_exists($offset, $this->properties)) {
            $methodName = 'get' . Str::studly($offset) . 'Property';
            if (method_exists($this, $methodName)) {
                return $this->{$methodName}();
            }
        }

        return $this->properties[$offset];
    }

    /**
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $methodName = 'set' . Str::studly($offset) . 'Property';
        if (method_exists($this, $methodName)) {
            $this->{$methodName}($value);
        } else {
            $this->properties[$offset] = $value;
        }
    }

    /**
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->properties);
    }
}