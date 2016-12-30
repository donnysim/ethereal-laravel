<?php

namespace Ethereal\Database\Relations;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Manager
{
    const OPTION_SKIP = 1;
    const OPTION_SAVE = 2;
    const OPTION_DELETE = 4;
    const OPTION_ATTACH = 8;
    const OPTION_SYNC = 16;
    const OPTION_DETACH = 32;

    /**
     * Registered relation handlers.
     *
     * @var array
     */
    protected static $handlers = [

    ];

    /**
     * Save options.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $options;

    /**
     * Root model that initiated saving.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $root;

    /**
     * Actions to execute before parent model is saved.
     *
     * @var array
     */
    protected $beforeParentSave = [];

    /**
     * Actions to execute after parent model was saved.
     *
     * @var array
     */
    protected $afterParentSave = [];

    /**
     * Manager constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model $root
     * @param \Illuminate\Support\Collection|array $options
     */
    public function __construct(Model $root, $options)
    {
        if (is_array($options)) {
            $options = new SupportCollection($options);
        }

        $this->root = $root;
        $this->options = $options;

        // Make sure relation options is available as instance of collection
        if (!$this->options->has('relations')) {
            $this->options->put('relations', new SupportCollection);
        } elseif (!$this->options->get('relations') instanceof SupportCollection) {
            $this->options->put('relations', new SupportCollection($this->options->get('relations')));
        }

//        $this->buildQueue($root);
    }

    /**
     * Add action to execute before parent is saved.
     *
     * @param \Closure $closure
     */
    public function before(Closure $closure)
    {
        $this->beforeParentSave[] = $closure;
    }

    /**
     * Add action to execute after parent was saved.
     *
     * @param \Closure $closure
     */
    public function after(Closure $closure)
    {
        $this->afterParentSave[] = $closure;
    }

    /**
     * Get options.
     *
     * @return array|\Illuminate\Support\Collection
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get relation options.
     *
     * @param string $key
     *
     * @return int
     *   Returns default options if key was not found.
     */
    public function getRelationOptions($key)
    {
        if (!Str::contains($key, '.')) {
            return Arr::get($this->options, "relations.{$key}", static::getDefaultOptions());
        }

        $segments = explode('.', $key);
        $lastResult = null;
        $segment = 'relations';

        foreach ($segments as $part) {
            $segment .= ".{$part}";
            $options = Arr::get($this->options, $segment, null);

            if ($options === null && $lastResult === null) {
                return static::getDefaultOptions();
            } elseif ($options === static::OPTION_SKIP) {
                return static::OPTION_SKIP;
            } elseif ($options === null && !$lastResult) {
                return $lastResult;
            }

            $lastResult = $options;
        }

        if ($lastResult === null) {
            return static::getDefaultOptions();
        }

        return $lastResult;
    }

    /**
     * Register or replace relation handler.
     *
     * @param string $relation
     * @param string $handler
     *
     * @throws \InvalidArgumentException
     */
    public static function register($relation, $handler)
    {
        if (!is_string($relation) || !is_string($handler)) {
            throw new InvalidArgumentException('Expected a class string.');
        }

        static::$handlers[$relation] = $handler;
    }

    /**
     * Check if manager can handle relation.
     *
     * @param string|\Illuminate\Database\Eloquent\Relations\Relation $relation
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function canHandle($relation)
    {
        if (!is_string($relation) && !$relation instanceof Relation) {
            throw new InvalidArgumentException('Expected a string or Relation.');
        }

        if ($relation instanceof Relation) {
            $relation = get_class($relation);
        }

        return isset(static::$handlers[$relation]);
    }

    /**
     * Get default relation save options.
     *
     * @return int
     */
    public static function getDefaultOptions()
    {
        return static::OPTION_SAVE | static::OPTION_ATTACH;
    }

    /**
     * Determine if the relation should be skipped from saving.
     *
     * @param mixed $data
     * @param int $options
     * @param \Illuminate\Database\Eloquent\Model|null $parent
     * @param string|null $relationName
     *
     * @return bool
     */
    public static function shouldSkipRelation($data, $options, Model $parent = null, $relationName = null)
    {
        // Marked to skip
        if ($options & static::OPTION_SKIP) {
            return true;
        }

        // Invalid types
        if (!$data || (!$data instanceof Collection && !$data instanceof Model)) {
            return true;
        }

        // Relation initiation should exist
        if ($parent && $relationName && !method_exists($parent, $relationName)) {
            return true;
        }

        return false;
    }

    /**
     * Check if object is soft deleting.
     *
     * @param mixed $object
     *
     * @return bool
     */
    public static function isSoftDeleting($object)
    {
        return method_exists($object, 'bootSoftDeletes');
    }
}
