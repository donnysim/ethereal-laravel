<?php

namespace Ethereal\Database\Relations\Handlers;

use Ethereal\Database\Relations\Exceptions\InvalidTypeException;
use Ethereal\Database\Relations\RelationHandler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;

abstract class Handler implements RelationHandler
{
    /**
     * Target relation.
     *
     * @var \Illuminate\Database\Eloquent\Relations\Relation
     */
    protected $relation;

    /**
     * Target relation name on parent.
     *
     * @var string
     */
    protected $relationName;

    /**
     * Model class that the relation consists of.
     *
     * @var string
     */
    protected $relationModelClass;

    /**
     * The model or model collection to be parsed.
     *
     * @var \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|array
     */
    protected $data;

    /**
     * Relation options.
     *
     * @var int
     */
    protected $options;

    /**
     * Relation manager.
     *
     * @var \Ethereal\Database\Relations\Manager
     */
    protected $manager;

    /**
     * BaseRelationHandler constructor.
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
     * @param string $relationName
     * @param array|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model $data
     * @param int $options
     */
    public function __construct(Relation $relation, $relationName, $data, $options)
    {
        $this->relation = $relation;
        $this->relationName = $relationName;
        $this->data = $data;
        $this->options = $options;
    }

    /**
     * Set relation manager.
     *
     * @param \Ethereal\Database\Relations\Manager $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * Create an instance of model from given attributes.
     *
     * @param mixed $data
     *
     * @return \Ethereal\Database\Ethereal
     * @throws \Ethereal\Database\Relations\Exceptions\InvalidTypeException
     */
    public function hydrateModel($data)
    {
        if ($data === null || $data instanceof Model) {
            return $data;
        }

        if (!is_array($data) || !Arr::isAssoc($data)) {
            throw new InvalidTypeException("`{$this->relationName}` relation hydration only accepts associative array and model as value.");
        }

        return $this->wrapModel($data);
    }

    /**
     * Hydrate a list of data into model collection.
     *
     * @param mixed $data
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \Ethereal\Database\Relations\Exceptions\InvalidTypeException
     */
    public function hydrateCollection($data)
    {
        if ($data === null || $data instanceof Collection) {
            return $data;
        }

        if (!is_array($data)) {
            throw new InvalidTypeException("`{$this->relationName}` relation hydration only accepts array or collection as value.");
        }

        $collection = new Collection();

        foreach ($data as $attributes) {
            $collection->add($this->hydrateModel($attributes));
        }

        return $collection;
    }

    /**
     * Create related model from attributes and set it's existence.
     *
     * @param array $data
     *
     * @return \Ethereal\Database\Ethereal
     */
    protected function wrapModel(array $data)
    {
        $modelClass = $this->getModelClass();

        /** @var \Ethereal\Database\Ethereal $model */
        $model = new $modelClass($data);
        $model->exists = isset($data[$model->getKeyName()]);

        return $model;
    }

    /**
     * Validate data types to match expectations of relation.
     *
     * @param $data
     *
     * @throws \Ethereal\Database\Relations\Exceptions\InvalidTypeException
     */
    protected function validateType($data)
    {
        $list = $data instanceof Collection ? $data : [$data];

        foreach ($list as $item) {
            if (!$item) {
                continue;
            }

            $class = get_class($item);

            if ($this->getModelClass() !== $class) {
                throw new InvalidTypeException("Invalid model type given for `{$this->relationName}` relation, `{$class}` given, {$this->getModelClass()} expected.");
            }
        }
    }

    /**
     * Get related model class.
     *
     * @return string
     */
    protected function getModelClass()
    {
        if (!$this->relationModelClass) {
            $this->relationModelClass = get_class($this->relation->getRelated());
        }

        return $this->relationModelClass;
    }
}
