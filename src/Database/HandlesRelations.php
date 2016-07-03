<?php

namespace Ethereal\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @mixin Ethereal
 * TODO: refactor because a lot of code repeats
 */
trait HandlesRelations
{
    /**
     * Whether to use smart relations setting.
     *
     * @var bool
     */
    protected $userSmartRelations = true;

    /**
     * Save all relations.
     *
     * @param array|Collection $options
     * @param bool $storeWaitingRelations
     * @return bool
     */
    public function saveRelations($options = [], $storeWaitingRelations = false)
    {
        // To sync all of the relationships to the database, we will simply spin through
        // the relationships and save each model.
        foreach ($this->relations as $relationName => $model) {

            $relationOptions = isset($options[$relationName])
                ? $options[$relationName]
                : Ethereal::OPTION_SAVE | Ethereal::OPTION_ATTACH;

            // Skip if null, relation function does not exist or should be skipped
            if ($model === null || ($relationOptions & Ethereal::OPTION_SKIP) || ! method_exists($this, $relationName)) {
                continue;
            }

            $relation = $this->{$relationName}();

            $method = 'save' . last(explode('\\', get_class($relation)));
            if (method_exists($this, $method)) {
                $result = $this->{$method}($relation, $model, $relationOptions);

                if ($result === true) {
                    if ($storeWaitingRelations) {
                        $options[$relationName] = Ethereal::OPTION_SKIP;
                    }
                    continue;
                } elseif ($result !== null) {
                    // null result marks that the relation requires model to exist
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Set the specific relationship in the model. No transformations are done.
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setRawRelation($name, $value)
    {
        if ($value === null) {
            unset($this->relations[$name]);
        } else {
            $this->relations[$name] = $value;
        }

        return $this;
    }

    /**
     * Set the specific relationship in the model.
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setRelation($name, $value)
    {
        if (! $this->userSmartRelations) {
            return $this->setRawRelation($name, $value);
        }

        if ($value === null) {
            unset($this->relations[$name]);
        } else {
            // Check if relation function exists, if not, we can't do
            // anything about it so we can skip it
            if (! method_exists($this, $name)) {
                $this->relations[$name] = $value;
            } else {
                $relation = $this->{$name}();
                $this->relations[$name] = $this->boxRelation($relation, $value);
            }
        }

        return $this;
    }

    /**
     * Box relation data into collection or model.
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
     * @param $data
     * @return mixed
     */
    protected function boxRelation(Relation $relation, $data)
    {
        $method = 'box' . last(explode('\\', get_class($relation)));
        if (method_exists($this, $method)) {
            return $this->{$method}($relation, $data);
        }

        return $data;
    }

    /**
     * Convert belongs to many data.
     *
     * @param \Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
     * @param $data
     * @return array|\Illuminate\Support\Collection
     * @throws InvalidArgumentException
     */
    protected function boxBelongsToMany(BelongsToMany $relation, $data)
    {
        if (is_array($data) && count($data) === 0) {
            return $data;
        }

        /** @var Model $class */
        $relatedModel = get_class($relation->getRelated());

        if ($data instanceof Collection) {
            if (! $data->isEmpty() && $data->first() instanceof $relatedModel) {
                return $data;
            }

            throw new InvalidArgumentException("Invalid BelongsToMany collection - collection should consist of {$relatedModel} objects.");
        }

        $container = new Collection;

        if ($data instanceof Model) {
            $container[] = $data;
        } elseif (is_array($data)) {

            if (Arr::isAssoc($data)) {
                // It's an object in array form
                /** @var Ethereal $model */
                $model = new $relatedModel($data);
                $model->exists = isset($model[$model->getKeyName()]);
                $container[] = $model;
            } elseif (is_numeric(head($data)) && ! Arr::isAssoc($data)) {
                return $data;
            } else {
                foreach ($data as $item) {
                    if ($item instanceof Model) {
                        $container[] = $item;
                    } else {
                        /** @var Ethereal $model */
                        $model = new $relatedModel($item);
                        $model->exists = isset($model[$model->getKeyName()]);
                        $container[] = $model;
                    }
                }
            }
        }

        return $container;
    }

    /**
     * Save BelongsToMany relation.
     *
     * @param \Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
     * @param $data
     * @param int $options
     * @return bool
     * @throws \Exception
     */
    protected function saveBelongsToMany(BelongsToMany $relation, $data, $options = Ethereal::OPTION_SAVE | Ethereal::OPTION_ATTACH)
    {
        // This relation requires the parent model to be exist
        if (! $this->exists) {
            return null;
        }

        if ($options & Ethereal::OPTION_SKIP) {
            return true;
        }

        $ids = [];

        if (is_array($data)) {
            // If it's an array, only actions we can take is sync/attach/detach
            $ids = $data;
        } else {
            foreach ($data as $item) {
                /** @var Ethereal|mixed $item */
                if ($options & Ethereal::OPTION_SAVE) {
                    $item->save();
                    $ids[] = $item->getKey();
                } elseif ($options & Ethereal::OPTION_PUSH) {
                    $item->push();
                    $ids[] = $item->getKey();
                } elseif ($options & Ethereal::OPTION_DELETE) {
                    $ids[] = $item->getKey();
                    $item->delete();
                }
            }
        }

        if ($options & Ethereal::OPTION_ATTACH) {
            $relation->sync($ids, false);
        } elseif ($options & Ethereal::OPTION_DETACH) {
            $relation->detach($ids, false);
        } elseif ($options & Ethereal::OPTION_SYNC) {
            $relation->sync($ids);
        }

        return true;
    }

    /**
     * Convert has many data.
     *
     * @param \Illuminate\Database\Eloquent\Relations\HasMany $relation
     * @param $data
     * @return array|\Illuminate\Support\Collection
     * @throws \InvalidArgumentException
     */
    protected function boxHasMany(HasMany $relation, $data)
    {
        if (is_array($data) && count($data) === 0) {
            return $data;
        }

        /** @var Model $class */
        $relatedModel = get_class($relation->getRelated());

        if ($data instanceof Collection) {
            if (! $data->isEmpty() && $data->first() instanceof $relatedModel) {
                return $data;
            }

            throw new InvalidArgumentException("Invalid HasMany collection - collection should consist of {$relatedModel} objects.");
        }

        $container = new Collection();

        if ($data instanceof Model) {
            $container[] = $data;
        } elseif (is_array($data)) {
            if (Arr::isAssoc($data)) {
                // It's an object in array form
                /** @var Ethereal $model */
                $model = new $relatedModel($data);
                $model->exists = isset($model[$model->getKeyName()]);
                $container[] = $model;
            } else {
                foreach ($data as $item) {
                    if ($item instanceof Model) {
                        $container[] = $item;
                    } else {
                        /** @var Ethereal $model */
                        $model = new $relatedModel($item);
                        $model->exists = isset($model[$model->getKeyName()]);
                        $container[] = $model;
                    }
                }
            }
        }

        return $container;
    }

    /**
     * Save HasMany relation.
     *
     * @param \Illuminate\Database\Eloquent\Relations\HasMany $relation
     * @param Collection $data
     * @param int $options
     * @return bool|null|void
     * @throws \Exception
     */
    protected function saveHasMany(HasMany $relation, $data, $options = Ethereal::OPTION_SAVE)
    {
        // This relation requires the parent model to be exist
        if (! $this->exists) {
            return null;
        }

        // We can only save instance of collections
        if (! $data instanceof Collection) {
            return;
        }

        if ($options & Ethereal::OPTION_SKIP) {
            return true;
        }

        foreach ($data as $item) {
            /** @var Ethereal|mixed $item */

            $item[$relation->getPlainForeignKey()] = $this->getKey();

            if ($options & Ethereal::OPTION_SAVE) {
                $item->save();
            } elseif ($options & Ethereal::OPTION_PUSH) {
                $item->push();
            } elseif ($options & Ethereal::OPTION_DELETE) {
                if ($item->delete()) {
                    unset($data[$data->search(function ($i) use ($item) {
                            return $item->getKey() === $i->getKey();
                        })]);
                }
            }
        }

        return true;
    }

    /**
     * Convert has one data.
     *
     * @param \Illuminate\Database\Eloquent\Relations\HasOne $relation
     * @param $data
     * @return array|\Illuminate\Support\Collection
     * @throws \InvalidArgumentException
     */
    protected function boxHasOne(HasOne $relation, $data)
    {
        if (empty($data)) {
            return $data;
        }

        if ($data instanceof Model) {
            return $data;
        } elseif (Arr::isAssoc($data)) {
            $relatedModel = get_class($relation->getRelated());

            /** @var Ethereal $model */
            $model = new $relatedModel($data);
            $model->exists = isset($model[$model->getKeyName()]);

            return $model;
        }

        return $data;
    }

    /**
     * Save HasOne relation.
     *
     * @param \Illuminate\Database\Eloquent\Relations\HasOne $relation
     * @param Collection $data
     * @param int $options
     * @return bool|null|void
     * @throws \Exception
     */
    protected function saveHasOne(HasOne $relation, $data, $options = Ethereal::OPTION_SAVE)
    {
        // This relation requires the parent model to be exist
        if (! $this->exists) {
            return null;
        }

        // We can only save instance of model
        if (! $data instanceof Model) {
            return;
        }

        if ($options & Ethereal::OPTION_SKIP) {
            return true;
        }

        $data[$relation->getPlainForeignKey()] = $this->getKey();

        if ($options & Ethereal::OPTION_SAVE) {
            $data->save();
        } elseif ($options & Ethereal::OPTION_PUSH) {
            $data->push();
        } elseif ($options & Ethereal::OPTION_DELETE) {
            if ($data->delete()) {
                foreach ($this->relations as $name => $rel) {
                    if ($rel === $data) {
                        unset($name);
                        break;
                    }
                }
            }
        }

        return true;
    }
}