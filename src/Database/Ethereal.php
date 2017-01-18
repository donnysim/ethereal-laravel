<?php

namespace Ethereal\Database;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Support\Arr;

class Ethereal extends BaseModel
{
    use Traits\WithoutFillable,
        Traits\Validates,
        Traits\ExtendsRelations,
        Traits\Translatable;

    /**
     * Database columns. This is used to filter out invalid columns.
     *
     * @var string[]
     */
    protected $columns = [];

    /**
     * Fill the model with an array of attributes.
     *
     * @param array $attributes
     *
     * @return $this
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $key = $this->removeTableFromKey($key);

            if ($this->isRelationFillable($key)) {
                $this->setRelation($key, $value);
            }

            $this->setAttribute($key, $value);
        }

        return $this;
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
     * Determine if an attribute is present in the attributes list.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->attributes);
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
            return count(array_intersect_key(array_flip($attributes), $this->attributes)) === count($attributes);
        }

        return count(array_intersect_key(array_flip($attributes), $this->attributes)) > 0;
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
     * Keep only specific attributes and relations.
     *
     * @param array|string $keep
     *
     * @return $this
     */
    public function only($keep)
    {
        if (!is_array($keep)) {
            $keep = func_get_args();
        }

        $this->attributes = Arr::only($this->attributes, $keep);
        $this->relations = Arr::only($this->relations, $keep);

        return $this;
    }

    /**
     * Keep all attributes and relations except specific ones.
     *
     * @param array|string $remove
     *
     * @return $this
     */
    public function except($remove)
    {
        if (!is_array($remove)) {
            $remove = func_get_args();
        }

        $this->attributes = Arr::except($this->attributes, $remove);
        $this->relations = Arr::except($this->relations, $remove);

        return $this;
    }

    /**
     * Determine if the model is soft deleting.
     *
     * @return bool
     */
    public function isSoftDeleting()
    {
        return method_exists($this, 'bootSoftDeletes');
    }

    /**
     * Get the attributes that have been changed since last sync.
     *
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];
        $columns = empty($this->columns) ? $this->attributes : Arr::only($this->attributes, $this->getColumns());

        foreach ($columns as $key => $value) {
            if (!array_key_exists($key, $this->original)) {
                $dirty[$key] = $value;
            } elseif ($value !== $this->original[$key] &&
                !$this->originalIsNumericallyEquivalent($key)
            ) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Get an attribute from the model or it's translation.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if ($this->translatable() && in_array($key, $this->translatable, true)) {
            return $this->trans()->{$key};
        }

        return parent::getAttribute($key);
    }

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
     * Set a list of columns this model table contains.
     *
     * @param array $columns
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * Refresh model data from database.
     *
     * @param array|null $attributes
     *
     * @return $this
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function refresh($attributes = null)
    {
        if (!$this->exists) {
            return $this;
        }

        /** @var Ethereal $freshModel */
        $freshModel = $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey())->firstOrFail($attributes ?: ['*']);

        if ($attributes) {
            $this->setRawAttributes(
                array_merge($this->getAttributes(), Arr::only($freshModel->getAttributes(), $attributes))
            );

            foreach ($attributes as $attribute) {
                if (array_key_exists($attribute, $this->attributes)) {
                    $this->syncOriginalAttribute($attribute);
                }
            }
        } else {
            $this->setRawAttributes($freshModel->getAttributes(), true);
        }

        return $this;
    }
}
