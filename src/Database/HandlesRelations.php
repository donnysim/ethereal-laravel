<?php

namespace Ethereal\Database;

use Ethereal\Database\Relations\RelationManager;
use Ethereal\Database\Relations\RelationProcessor;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait HandlesRelations
{
    /**
     * Use smart relations by default.
     *
     * @var bool
     */
    protected $useSmartRelations = true;

    /**
     * Remove deleted relations on delete by default.
     *
     * @var bool
     */
    protected $removeRelationModelOnDelete = true;

    /**
     * Register a syncing model event with the dispatcher.
     *
     * @param \Closure|string $callback
     * @param int $priority
     */
    public static function syncing($callback, $priority = 0)
    {
        static::registerModelEvent('syncing', $callback, $priority);
    }

    /**
     * Set the specific relationship in the model.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setRelation($name, $value)
    {
        if (!$this->useSmartRelations || $value === null) {
            return $this->setRawRelation($name, $value);
        }

        // Check if relation function exists, if not, we can't do
        // anything about it so we can skip it
        if (method_exists($this, $name)) {
            $relation = $this->{$name}();

            if (!$relation instanceof Relation) {
                throw new \InvalidArgumentException("`{$name}` is not a valid relation.");
            }

            if (RelationManager::canHandle($relation)) {
                $this->relations[$name] = RelationManager::makeHandler($relation, $name, $this, $value)->build();
            } else {
                return $this->setRawRelation($name, $value);
            }
        } else {
            $this->relations[$name] = $value;
        }

        return $this;
    }

    /**
     * Set the specific relationship in the model. No transformations are done.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setRawRelation($name, $value)
    {
        $this->relations[$name] = $value;

        return $this;
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (Str::endsWith($method, 'Handler')) {
            $relation = substr($method, 0, -7);

            if (method_exists($this, $relation)) {
                $parametersCount = count($parameters);
                $autoload = $parametersCount > 0 ? (bool)$parameters[0] : false;

                if (!$this->relationLoaded($relation)) {
                    if ($autoload) {
                        if ($parametersCount > 1) {
                            $this->load([$relation => $parameters[1]]);
                        } else {
                            $this->load($relation);
                        }
                    } else {
                        return null;
                    }
                }

                return RelationManager::makeHandler($this->{$relation}(), $relation, $this, $this->getRelation($relation));
            }
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Save all model relations.
     *
     * @param array $options
     *
     * @return bool
     */
    protected function saveRelations(array $options = [])
    {
        if (!isset($options['removeRelationModelOnDelete'])) {
            $options['removeRelationModelOnDelete'] = $this->removeRelationModelOnDelete;
        }

        return (new RelationProcessor($this, new Collection($options)))->handle();
    }
}
