<?php

namespace Modules\Payments\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class MorphToAll extends Relation
{
    /**
     * Pivot model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $pivotModel;

    /**
     * Pivot relation accessor name.
     *
     * @var string
     */
    protected $accessor = 'pivot';

    /**
     * Pivot morph name.
     *
     * @var string
     */
    protected $pivotMorphName;

    /**
     * Parent model foreign key.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * MorphToMixedCollection constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param \Illuminate\Database\Eloquent\Model $pivot
     * @param string $foreignKey
     * @param string $morphName
     */
    public function __construct(Model $parent, Model $pivot, $foreignKey, $morphName)
    {
        $this->pivotModel = $pivot;
        $this->pivotMorphName = $morphName;
        $this->foreignKey = $foreignKey;

        parent::__construct($pivot->newQuery(), $parent);
    }

    /**
     * Set the base constraints on the relation query.
     */
    public function addConstraints()
    {
        //
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $models
     */
    public function addEagerConstraints(array $models)
    {
        $this->query->whereIn($this->foreignKey, $this->getKeys($models));
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param array $models
     * @param string $relation
     *
     * @return array
     */
    public function initRelation(array $models, $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param array $models
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @param string $relation
     *
     * @return array
     */
    public function match(array $models, Collection $results, $relation): array
    {
        if (\count($models) === 0) {
            return $models;
        }

        $dictionary = collect($models)->keyBy($this->parent->getKeyName());

        foreach ($results as $result) {
            $dictionary->get($result->{$this->accessor}->{$this->foreignKey})->{$relation}->push($result);
        }

        return $models;
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->get();
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*']): EloquentCollection
    {
        $with = $this->query->getEagerLoads();
        $this->query->setEagerLoads([]);

        $pivotsByType = collect($this->query->applyScopes()->getModels())->groupBy("{$this->pivotMorphName}_type");

        $models = [];
        foreach ($pivotsByType as $type => $pivots) {
            $pivotCollection = collect($pivots);
            $modelClass = Relation::getMorphedModel($type);
            $model = new $modelClass();
            $modelQuery = $modelClass::query();
            $results = $modelQuery->whereIn($model->getKeyName(), $pivotCollection->pluck("{$this->pivotMorphName}_id"))->get();

            if ($results->isNotEmpty() && \count($with) > 0) {
                $relations = [];

                foreach ($with as $name => $closure) {
                    if (method_exists($model, $name)) {
                        $relations[$name] = $closure;
                    }
                }

                $modelQuery->setEagerLoads($relations);
                $modelQuery->eagerLoadRelations($results->all());
            }

            foreach ($results as $result) {
                $result->setRelation($this->accessor, $pivotCollection->first(function (Model $item) use ($result) {
                    return $item->{"{$this->pivotMorphName}_id"} === $result->getKey();
                }));

                $models[] = $result;
            }
        }

        return $this->related->newCollection($models);
    }

    /**
     * Specify the custom pivot accessor to use for the relationship.
     *
     * @param string $accessor
     *
     * @return \Modules\Payments\Relations\MorphToAll
     */
    public function as($accessor): MorphToAll
    {
        $this->accessor = $accessor;

        return $this;
    }
}
