<?php

namespace Ethereal\Support;

use ArrayAccess;
use Closure;
use Ethereal\Http\JsonResponse;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @property \Ethereal\Http\JsonResponse json
 */
abstract class FluentController extends Controller implements ArrayAccess
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
     * Dependency container.
     *
     * @var Container
     */
    protected $container;

    /**
     * Last initiated validator.
     *
     * @var \Illuminate\Validation\Validator|null
     */
    protected $validator;

    /**
     * Controller constructor.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Contracts\Container\Container $container
     */
    public function __construct(Request $request, Container $container)
    {
        $this->request = $request;
        $this->container = $container;
    }

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
     * is triggered by calling isset() or empty() on inaccessible members.
     *
     * @param $name string
     * @return bool
     * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __isset($name)
    {
        return isset($this->properties[$name]);
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
     * Check if cached value exists, if not cache it, and store as result.
     *
     * @param string $name Store result as.
     * @param string $key Cache key.
     * @param \DateTime|int $duration Duration to remember. -1 means forever.
     * @param \Closure $callback
     * @return $this
     */
    protected function cacheAs($name, $key, $duration, Closure $callback)
    {
        /** @var \Illuminate\Cache\CacheManager|\Illuminate\Cache\Repository $cache */
        $cache = $this->container->make('cache');

        if ($duration === -1) {
            $this[$name] = $cache->rememberForever($key, $callback);
        } else {
            $this[$name] = $cache->remember($key, $duration, $callback);
        }

        return $this;
    }

    /**
     * Build a json response.
     *
     * @param array|Closure|null $payload
     * @return \Ethereal\Http\JsonResponse
     */
    protected function json($payload = null)
    {
        if (is_array($payload)) {
            $this->json->attachData($payload);
        } elseif ($payload instanceof Closure) {
            $payload($this->json);
        }

        return $this->json;
    }

    /**
     * Validate current request.
     *
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return \Ethereal\Support\FluentController
     */
    protected function validateRequest(array $rules = [], array $messages = [], array $customAttributes = [])
    {
        return $this->validate($this->request->all(), $rules, $messages, $customAttributes);
    }

    /**
     * Validate data, if validation fails, throw an exception.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return $this
     */
    protected function validate(array $data = [], array $rules = [], array $messages = [], array $customAttributes = [])
    {
        $this->validator = $this->validationFactory()->make($data, $rules, $messages, $customAttributes);

        if ($this->validator->fails()) {
            $this->throwValidationException($this->validator);
        }

        return $this;
    }

    /**
     * Get validation factory.
     *
     * @return Factory
     */
    protected function validationFactory()
    {
        return app(Factory::class);
    }

    /**
     * Throw validation exception.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwValidationException($validator)
    {
        if (($this->request->ajax() && ! $this->request->pjax()) || $this->request->wantsJson()) {
            $response = JsonResponse::make(null, 422)->error($validator);
        } else {
            $response = redirect()->back()->withInput($this->request->input())->withErrors($validator->messages());
        }

        throw new ValidationException($validator, $response);
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
}