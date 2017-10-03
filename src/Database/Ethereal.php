<?php

namespace Ethereal\Database;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Support\Arr;

class Ethereal extends BaseModel
{
    use Traits\WithoutFillable;

    /**
     * Database columns. This is used to filter out invalid columns.
     *
     * @var string[]
     */
    protected $columns = [];

    /**
     * Get a list of columns this model table contains.
     *
     * @return string[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get the attributes that have been changed since last sync.
     *
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];
        $attributes = empty($this->getColumns()) ? $this->getAttributes() : Arr::only($this->getAttributes(), $this->getColumns());

        foreach ($attributes as $key => $value) {
            if (!$this->originalIsEquivalent($key, $value)) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Determine if an attribute is present in the attributes list.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute($name)
    {
        return \array_key_exists($name, $this->attributes);
    }

    /**
     * Determine if all ony any of the attributes are present.
     *
     * @param array $attributes
     * @param bool $all
     *
     * @return bool
     */
    public function hasAttributes(array $attributes, $all = true)
    {
        if ($all) {
            return \count(\array_intersect_key(\array_flip($attributes), $this->attributes)) === \count($attributes);
        }

        return \count(\array_intersect_key(\array_flip($attributes), $this->attributes)) > 0;
    }

    /**
     * Determine if the model is soft deleting.
     *
     * @return bool
     */
    public function isSoftDeleting()
    {
        return \method_exists($this, 'bootSoftDeletes');
    }

    /**
     * Keep all attributes and relations except specific ones.
     *
     * @param array|string $remove
     *
     * @return $this
     */
    public function keepExcept($remove)
    {
        if (!\is_array($remove)) {
            $remove = \func_get_args();
        }

        $this->attributes = Arr::except($this->attributes, $remove);
        $this->relations = Arr::except($this->relations, $remove);

        return $this;
    }

    /**
     * Keep only specific attributes and relations.
     *
     * @param array|string $keep
     *
     * @return $this
     */
    public function keepOnly($keep)
    {
        if (!\is_array($keep)) {
            $keep = \func_get_args();
        }

        $this->attributes = Arr::only($this->attributes, $keep);
        $this->relations = Arr::only($this->relations, $keep);

        return $this;
    }

    /**
     * Set a list of columns this model table contains.
     *
     * @param array $columns
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * Set model key value.
     *
     * @param string|int $value
     */
    public function setKey($value)
    {
        $this->setAttribute($this->getKeyName(), $value);
    }

    /**
     * Set attribute value without morphing.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setRawAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Perform a model insert operation.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return bool
     */
    protected function performInsert(EloquentBuilder $query)
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        $attributes = empty($this->columns) ? $this->attributes : Arr::only($this->attributes, $this->getColumns());

        if ($this->getIncrementing()) {
            $this->insertAndSetId($query, $attributes);
        }

        // If the table isn't incrementing we'll simply insert these attributes as they
        // are. These attribute arrays must contain an "id" column previously placed
        // there by the developer as the manually determined key for these models.
        else {
            if (empty($attributes)) {
                return true;
            }

            $query->insert($attributes);
        }

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;

        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created', false);

        return true;
    }
}
