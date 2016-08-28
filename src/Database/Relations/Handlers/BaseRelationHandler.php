<?php

namespace Ethereal\Database\Relations\Handlers;

use Ethereal\Contracts\Database\RelationHandler;
use Ethereal\Database\Relations\RelationProcessor;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

abstract class BaseRelationHandler implements RelationHandler
{
    /**
     * @var Relation
     */
    protected $relation;

    /**
     * @var Model
     */
    protected $parent;

    /**
     * @var string
     */
    protected $relationName;

    /**
     * @var Model|EloquentCollection
     */
    protected $data;

    /**
     * @var int
     */
    protected $relationOptions;

    /**
     * @var Collection
     */
    protected $options;

    /**
     * @var string
     */
    protected $optionName;

    /**
     * Relation helper that created this handler.
     *
     * @var \Ethereal\Database\Relations\RelationProcessor
     */
    private $processor;

    /**
     * BaseRelationHandler constructor.
     *
     * @param Relation $relation
     * @param string $relationName
     * @param Model $parent
     * @param EloquentCollection|Model $data
     * @param int $relationOptions
     * @param Collection $options
     * @param string $optionName
     */
    public function __construct(Relation $relation, $relationName, Model $parent, $data, $relationOptions, Collection $options, $optionName)
    {
        $this->relation = $relation;
        $this->parent = $parent;
        $this->relationName = $relationName;
        $this->data = $data;
        $this->relationOptions = $relationOptions;
        $this->options = $options;
        $this->optionName = $optionName;
    }

    /**
     * Create model from given data.
     *
     * @param array|\Illuminate\Database\Eloquent\Model|null $data
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function boxModel($data = null)
    {
        if ($data === null) {
            $data = $this->data;
        }

        if ($data instanceof Model) {
            return $this;
        }

        if ((! $data instanceof Model && ! is_array($data)) || (is_array($data) && ! Arr::isAssoc($data))) {
            throw new InvalidArgumentException("`{$this->relationName}` relation only accepts associative array and model as value.");
        }

        $this->data = static::createRelationModel($this->relation->getRelated(), $data);

        return $this;
    }

    /**
     * Create model collection from given data.
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function boxCollection()
    {
        $collection = EloquentCollection::make();

        if (! is_array($this->data) && ! $this->data instanceof Collection) {
            throw new InvalidArgumentException("`{$this->relationName}` relation only accepts array or collection.");
        }

        foreach ($this->data as $item) {
            if ($item instanceof Model) {
                $collection->add($item);
            } elseif (! is_array($item) || ! Arr::isAssoc($item)) {
                throw new InvalidArgumentException("`{$this->relationName}` relation should contain valid model entries.");
            } else {
                if (empty($item)) {
                    throw new InvalidArgumentException("`{$this->relationName}` relation only accepts associative array and model as value.");
                }

                $collection->add(static::createRelationModel($this->relation->getRelated(), $item));
            }
        }

        $this->data = $collection;

        return $this;
    }

    /**
     * Set relation to null on parent model.
     */
    protected function removeModelRelation()
    {
        $this->parent->setRelation($this->relationName, null);
    }

    /**
     * Set relation to null on parent model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    protected function removeCollectionRelation(Model $model)
    {
        /** @var Collection $collection */
        $collection = $this->parent->getRelation($this->relationName);

        foreach ($collection as $index => $item) {
            if ($item === $model) {
                $collection->forget($index);
                break;
            }
        }
    }

    /**
     * Validate model class.
     *
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model $model
     * @throws \InvalidArgumentException
     */
    protected function validateClass($model)
    {
        $expected = get_class($this->relation->getRelated());
        $data = $model instanceof Collection ? $model : [$model];

        foreach ($data as $item) {
            $given = get_class($item);

            if ($expected !== $given) {
                throw new InvalidArgumentException("Invalid model given for `{$this->relationName}` relation - `{$expected}` expected, `{$given}` given.");
            }
        }
    }

    /**
     * Check if relation should be unset after delete operation.
     *
     * @return bool
     */
    protected function shouldRemoveAfterDelete()
    {
        return isset($this->options['removeRelationModelOnDelete']) && $this->options['removeRelationModelOnDelete'];
    }

    /**
     * Create new model and set existence.
     *
     * @param string|\Illuminate\Database\Eloquent\Model $relatedModel
     * @param array|\Illuminate\Database\Eloquent\Model $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function createRelationModel($relatedModel, $data)
    {
        if ($data instanceof Model) {
            return $data;
        }

        if (! is_string($relatedModel)) {
            $relatedModel = get_class($relatedModel);
        }

        /** @var Model $model */
        $model = new $relatedModel($data);
        $keyName = $model->getKeyName();
        if (isset($data[$keyName])) {
            $model->setAttribute($keyName, $data[$keyName]);
            $model->exists = $relatedModel::where($keyName, '=', $model->getKey())->exists();
        }

        return $model;
    }

    /**
     * Add pre queue action.
     *
     * @param mixed $action
     */
    public function before($action)
    {
        $this->processor->before($action);
    }

    /**
     * Add post queue action.
     *
     * @param mixed $action
     */
    public function after($action)
    {
        $this->processor->after($action);
    }

    /**
     * Set processor.
     *
     * @param \Ethereal\Database\Relations\RelationProcessor $processor
     */
    public function setProcessor(RelationProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Fire model event.
     *
     * @param string $event
     * @param array $payload
     * @param bool $halt
     * @return bool
     */
    protected function fireModelEvent($event, array $payload = [], $halt = true)
    {
        $dispatcher = Model::getEventDispatcher();

        if ($dispatcher === null) {
            return true;
        }

        // We will append the names of the class to the event to distinguish it from
        // other model events that are fired, allowing us to listen on each model
        // event set individually instead of catching event for all the models.
        $event = "eloquent.{$event}: " . get_class($this->parent);

        $method = $halt ? 'until' : 'fire';

        return $dispatcher->$method($event, $payload);
    }
}
