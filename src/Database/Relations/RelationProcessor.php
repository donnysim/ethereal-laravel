<?php

namespace Ethereal\Database\Relations;

use Ethereal\Contracts\Database\RelationHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RelationProcessor
{
    /**
     * Action to take before saving root model.
     *
     * @var array
     */
    protected $before = [];

    /**
     * Action to take after saving root model.
     *
     * @var array
     */
    protected $after = [];

    /**
     * Main root model instance that was passed when starting relation saving.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $rootModel;

    /**
     * Options that were passed for saving.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $options;

    /**
     * RelationHelper constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \Illuminate\Support\Collection $options
     */
    public function __construct(Model $model, Collection $options)
    {
        $this->rootModel = $model;
        $this->options = $options;

        // Make sure relations options is available and instance of collection
        if (!$this->options->has('relations')) {
            $this->options->put('relations', new Collection);
        } elseif (!$this->options->get('relations') instanceof Collection) {
            $this->options->put('relations', new Collection($this->options->get('relations')));
        }

        $this->buildQueue($model);
    }

    /**
     * Build before and after event queues.
     *
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Support\Collection|array $data
     * @param string $optionsRoot
     */
    protected function buildQueue($data, $optionsRoot = '')
    {
        if ($data instanceof Model) {
            $this->buildRelationsQueue($data, $optionsRoot);
        } elseif ($data instanceof Collection || is_array($data)) {
            foreach ($data as $model) {
                if ($model instanceof Model) {
                    $this->buildRelationsQueue($model, $optionsRoot);
                }
            }
        }
    }

    /**
     * Build queue events for parent and it's relations.
     *
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $optionsRoot
     */
    protected function buildRelationsQueue(Model $parent, $optionsRoot = '')
    {
        foreach ($parent->getRelations() as $relationName => $data) {
            $optionName = $this->getRelationOptionsName($optionsRoot, $relationName);
            $relationOptions = $this->getRelationOptions($optionName);

            if (RelationManager::relationShouldBeSkipped($relationName, $parent, $data, $relationOptions)) {
                continue;
            }

            /** @var \Illuminate\Database\Eloquent\Relations\Relation $relation */
            $relation = $parent->{$relationName}();

            if (!RelationManager::canHandle($relation)) {
                continue;
            }

            $handler = RelationManager::makeHandler($relation, $relationName, $parent, $data, $this, $relationOptions, $this->options, $optionName);

            if ($handler->isWaitingForParent()) {
                $this->after[] = $handler;
            } else {
                $this->before[] = $handler;
            }

            $this->buildQueue($data, $optionName);
        }
    }

    /**
     * Get name for relation options.
     *
     * @param string $root
     * @param string $relation
     *
     * @return string
     */
    protected function getRelationOptionsName($root, $relation)
    {
        if ($root === '') {
            return $relation;
        }

        return "{$root}.{$relation}";
    }

    /**
     * Get specific relation options.
     *
     * @param string $key
     *
     * @return int
     */
    protected function getRelationOptions($key)
    {
        if (!$this->options['relations']->has($key)) {
            return RelationManager::getDefaultOptions();
        }

        return $this->options['relations']->get($key);
    }

    /**
     * Handle all relation processing.
     * This includes parent saving.
     *
     * @return bool
     */
    public function handle()
    {
        $this->handleBeforeParent();

        $this->rootModel->save();

        $this->handleAfterParent();

        return true;
    }

    /**
     * Process events before parent model save.
     */
    protected function handleBeforeParent()
    {
        $this->runQueue($this->before);
    }

    /**
     * Process queue events.
     *
     * @param array $queue
     */
    protected function runQueue(array $queue)
    {
        while ($action = array_shift($queue)) {
            if ($action instanceof RelationHandler) {
                $action->save();
            } elseif ($action instanceof \Closure) {
                $action();
            }
        }
    }

    /**
     * Process events after parent model save.
     */
    protected function handleAfterParent()
    {
        $this->runQueue($this->after);
    }

    /**
     * Append action to before queue.
     *
     * @param mixed $action
     */
    public function before($action)
    {
        $this->before[] = $action;
    }

    /**
     * Append action to after queue.
     *
     * @param mixed $action
     */
    public function after($action)
    {
        $this->after[] = $action;
    }
}
