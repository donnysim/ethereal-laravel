<?php

namespace Ethereal\Database;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Support\Arr;

class Ethereal extends BaseModel
{
    use Traits\WithoutFillable,
        Traits\Validates,
        Traits\ExtendsRelations;

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
}
