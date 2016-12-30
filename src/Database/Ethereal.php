<?php

namespace Ethereal\Database;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Support\Arr;

class Ethereal extends BaseModel
{
    use Traits\WithoutFillable,
        Traits\Validates;

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
}
