<?php

namespace Ethereal\Database\Relations;

use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection as SupportCollection;

class RelationManager
{
    protected static $handlers = [
        BelongsTo::class => Handlers\BelongsToHandler::class,
        BelongsToMany::class => Handlers\BelongsToManyHandler::class,
        HasOne::class => Handlers\HasOneHandler::class,
        HasMany::class => Handlers\HasManyHandler::class,
    ];

    /**
     * Make relation handler.
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
     * @param string $relationName
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param mixed $data
     * @param \Ethereal\Database\Relations\RelationProcessor|null $processor
     * @param int|null $relationOptions
     * @param \Illuminate\Support\Collection|array|null $options
     * @param string $optionName
     *
     * @return \Ethereal\Contracts\Database\RelationHandler|null
     */
    public static function makeHandler(Relation $relation, $relationName, Model $parent, $data, RelationProcessor $processor = null, $relationOptions = null, $options = null, $optionName = '')
    {
        if (!static::canHandle($relation)) {
            return null;
        }

        if ($relationOptions === null) {
            $relationOptions = static::getDefaultOptions();
        }

        if ($options === null) {
            $options = new SupportCollection();
        }

        $class = static::$handlers[get_class($relation)];

        $handler = new $class($relation, $relationName, $parent, $data, $relationOptions, $options, $optionName);

        if ($processor && method_exists($handler, 'setProcessor')) {
            $handler->setProcessor($processor);
        }

        return $handler;
    }

    /**
     * Check if a handler is available for relation.
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
     *
     * @return bool
     */
    public static function canHandle(Relation $relation)
    {
        return isset(static::$handlers[get_class($relation)]);
    }

    /**
     * Get default relation save options.
     *
     * @return int
     */
    public static function getDefaultOptions()
    {
        return Ethereal::OPTION_SAVE | Ethereal::OPTION_ATTACH;
    }

    /**
     * Check if relation should be skipped when saving.
     *
     * @param string $relationName
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model $data
     * @param int $options
     *
     * @return bool
     */
    public static function relationShouldBeSkipped($relationName, Model $parent, $data, $options)
    {
        // Check if should be skipped
        if ($options & Ethereal::OPTION_SKIP) {
            return true;
        }

        // Skip invalid types
        if ($data === null || (!$data instanceof EloquentCollection && !$data instanceof Model)) {
            return true;
        }

        // Check if relation function exists
        if (!method_exists($parent, $relationName)) {
            return true;
        }

        return false;
    }

    /**
     * Check if model is soft deleting.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return bool
     */
    public static function isSoftDeleting($model)
    {
        if (!$model instanceof Model) {
            return false;
        }

        return method_exists($model, 'bootSoftDeletes');
    }
}
