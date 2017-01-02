<?php

namespace Ethereal\Database\Relations;

use Closure;
use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Manager
{
    const SKIP = 1;
    const SAVE = 2;
    const DELETE = 4;
    const ATTACH = 8;
    const SYNC = 16;
    const DETACH = 32;
    const SKIP_RELATIONS = 64;

    /**
     * Registered relation handlers.
     *
     * @var array
     */
    protected static $handlers = [
        BelongsTo::class => Handlers\BelongsToHandler::class,
        HasMany::class => Handlers\HasManyHandler::class,
        HasOne::class => Handlers\HasOneHandler::class,
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
     * @param \Ethereal\Database\Ethereal $root
     * @param \Illuminate\Support\Collection|array $options
     */
    public function __construct(Ethereal $root, $options = [])
    {
        if (!$options) {
            $options = new SupportCollection([]);
        } elseif (is_array($options)) {
            $options = new SupportCollection($options);
        }

        $this->root = $root;
        $this->options = $options;

        // Make sure relation options is available as instance of collection
        if (!$options->has('relations')) {
            $options->put('relations', new SupportCollection);
        } elseif (!$options->get('relations') instanceof SupportCollection) {
            $options->put('relations', new SupportCollection($options->get('relations')));
        }
    }

    /**
     * Save parent model and all it's relations.
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function save()
    {
        $this->beforeParentSave = [];
        $this->afterParentSave = [];

        $this->buildQueue($this->root);

        if (!$this->processQueue($this->beforeParentSave)) {
            return false;
        }

        if (!$this->root->save()) {
            return false;
        }

        if (!$this->processQueue($this->afterParentSave)) {
            return false;
        }

        return true;
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
        $segment = '';
        $skipRelations = false;

        foreach ($segments as $part) {
            if ($skipRelations) {
                return static::SKIP;
            }

            $segment = ltrim("{$segment}.{$part}", '.');
            $options = Arr::get($this->options->get('relations'), $segment, null);

            if ($options === null && $lastResult === null) {
                return static::getDefaultOptions();
            } elseif ($options === static::SKIP) {
                return static::SKIP;
            } elseif ($options === null && !$lastResult) {
                return $lastResult;
            }

            if ($options & static::SKIP_RELATIONS) {
                $skipRelations = true;
            }

            $lastResult = $options;
        }

        if ($lastResult === null) {
            return static::getDefaultOptions();
        }

        return $lastResult;
    }

    /**
     * Process queue actions.
     *
     * @param array $queue
     *
     * @return bool
     */
    protected function processQueue(array $queue)
    {
        while ($action = array_shift($queue)) {
            $action();
        }

        return true;
    }

    /**
     * Build after and before action queues.
     *
     * @param \Ethereal\Database\Ethereal|\Illuminate\Database\Eloquent\Collection $data
     * @param string $optionsRoot
     *
     * @throws \InvalidArgumentException
     */
    protected function buildQueue($data, $optionsRoot = '')
    {
        $models = [$data];

        if ($data instanceof Collection) {
            $models = $data;
        }

        foreach ($models as $model) {
            if ($model instanceof Ethereal) {
                $this->buildRelationsQueue($model, $optionsRoot);
            }
        }
    }

    /**
     * Build a queue for model of before and after events.
     *
     * @param \Ethereal\Database\Ethereal $parent
     * @param string $optionsRoot
     *
     * @throws \InvalidArgumentException
     */
    protected function buildRelationsQueue(Ethereal $parent, $optionsRoot = '')
    {
        foreach ($parent->getRelations() as $relationName => $data) {
            $optionsPath = $this->getRelationOptionsPath($relationName, $optionsRoot);
            $relationOptions = $this->getRelationOptions($optionsPath);

            if (static::shouldSkipRelation($data, $relationOptions, $parent, $relationName)) {
                continue;
            }

            /** @var \Illuminate\Database\Eloquent\Relations\Relation $relation */
            $relation = $parent->{$relationName}();

            if (!static::canHandle($relation)) {
                continue;
            }

            $handler = $this->makeHandler($relation, $relationName, $data, $relationOptions);
            $action = function () use ($handler) {
                return $handler->save();
            };

            if ($handler->isWaitingForParent()) {
                $this->after($action);
            } else {
                $this->before($action);
            }

            $this->buildQueue($data, $optionsPath);
        }
    }

    /**
     * Get full relation options path.
     *
     * @param string $relationName
     * @param string $root
     *
     * @return string
     */
    protected function getRelationOptionsPath($relationName, $root)
    {
        if (!$root) {
            return $relationName;
        }

        return "{$root}.{$relationName}";
    }

    /**
     * Create an instance of relation handler.
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
     * @param string $relationName
     * @param \Ethereal\Database\Ethereal|\Illuminate\Database\Eloquent\Collection $data
     * @param int $options
     *
     * @return \Ethereal\Database\Relations\RelationHandler|null
     * @throws \InvalidArgumentException
     */
    public function makeHandler(Relation $relation, $relationName, $data, $options)
    {
        if (!static::canHandle($relation)) {
            return null;
        }

        $handlerClass = static::$handlers[get_class($relation)];

        /** @var \Ethereal\Database\Relations\RelationHandler $handler */
        $handler = new $handlerClass($relation, $relationName, $data, $options);

        if (method_exists($handler, 'setManager')) {
            $handler->setManager($this);
        }

        return $handler;
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
        return static::SAVE | static::ATTACH;
    }

    /**
     * Determine if the relation should be skipped from saving.
     *
     * @param mixed $data
     * @param int $options
     * @param \Ethereal\Database\Ethereal|null $parent
     * @param string|null $relationName
     *
     * @return bool
     */
    public static function shouldSkipRelation($data, $options, Ethereal $parent = null, $relationName = null)
    {
        // Marked to skip
        if ($options & static::SKIP) {
            return true;
        }

        // Invalid types
        if (!$data || (!$data instanceof Collection && !$data instanceof Ethereal)) {
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
