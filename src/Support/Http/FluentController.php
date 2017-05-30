<?php

namespace Ethereal\Support\Http;

use Closure;
use Ethereal\Http\JsonResponse;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FluentController extends Controller
{
    const CACHE_FOREVER = -1;

    /**
     * Attribute values.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Last initiated validator.
     *
     * @var \Illuminate\Validation\Validator|null
     */
    protected $validator;

    /**
     * is utilized for reading data from inaccessible members.
     *
     * @param string $name
     *
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

        return null;
    }

    /**
     * run when writing data to inaccessible members.
     *
     * @param string $name
     * @param mixed $value
     *
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
     * @param string $name
     *
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
     * @param string $name
     *
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
     *
     * @param mixed $offset
     *
     * @return bool
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
     *
     * @param mixed $offset The offset to retrieve.
     *
     * @return mixed
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        if (!array_key_exists($offset, $this->properties)) {
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
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     *
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
     *
     * @param mixed $offset The offset to unset.

     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }

    /**
     * Check if cached value exists, if not cache it, and store as result.
     *
     * @param string $name Store result as.
     * @param string $key Cache key.
     * @param \DateTime|int $duration Duration to remember in minutes. -1 means forever.
     * @param \Closure $callback
     *
     * @return $this
     */
    protected function cacheAs($name, $key, $duration, Closure $callback)
    {
        /** @var \Illuminate\Cache\CacheManager|\Illuminate\Cache\Repository $cache */
        $cache = app('cache');

        if ($duration === static::CACHE_FOREVER) {
            $this[$name] = $cache->rememberForever($key, $callback);
        } else {
            $this[$name] = $cache->remember($key, $duration, $callback);
        }

        return $this;
    }

    /**
     * Validate current request.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     *
     * @return $this
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateRequest(Request $request, array $rules = [], array $messages = [], array $customAttributes = [])
    {
        return $this->validate($request, $request->all(), $rules, $messages, $customAttributes);
    }

    /**
     * Validate data, if validation fails, throw an exception.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     *
     * @return $this
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validate(Request $request, array $data = [], array $rules = [], array $messages = [], array $customAttributes = [])
    {
        $this->validator = $this->validationFactory()->make($data, $rules, $messages, $customAttributes);

        if ($this->validator->fails()) {
            $this->throwValidationException($request, $this->validator);
        }

        return $this;
    }

    /**
     * Get validation factory.
     *
     * @return \Illuminate\Contracts\Validation\Factory
     */
    protected function validationFactory()
    {
        return app(Factory::class);
    }

    /**
     * Throw validation exception.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Validation\Validator $validator
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \InvalidArgumentException
     */
    protected function throwValidationException(Request $request, $validator)
    {
        if ($request->expectsJson()) {
            $response = JsonResponse::make(null, 422)->error($validator);
        } else {
            $response = redirect()->back()->withInput($request->input())->withErrors($validator->messages());
        }

        throw new ValidationException($validator, $response);
    }

    /**
     * Get json response object.
     *
     * @return \Ethereal\Http\JsonResponse
     */
    protected function getJsonProperty()
    {
        return JsonResponse::make();
    }
}
