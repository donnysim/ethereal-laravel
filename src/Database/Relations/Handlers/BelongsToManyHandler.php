<?php

namespace Ethereal\Database\Relations\Handlers;

use Ethereal\Database\Relations\Exceptions\InvalidTypeException;
use Ethereal\Database\Relations\Manager;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class BelongsToManyHandler extends Handler
{
    const NORMAL = 1;
    const SYNC = 2;

    /**
     * Wrap data into model or collection of models based on relation type.
     *
     * @return \Ethereal\Database\Ethereal|\Illuminate\Database\Eloquent\Collection
     * @throws \Ethereal\Database\Relations\Exceptions\InvalidTypeException
     */
    public function build()
    {
        $type = static::getArrayType($this->data);

        if ($type === static::NORMAL) {
            $data = $this->hydrateCollection($this->data);
        } else {
            $data = new Collection($this->data);
        }

        $this->validate($data);

        return $data;
    }

    /**
     * Save relation data.
     *
     * @return bool
     * @throws \Ethereal\Database\Relations\Exceptions\InvalidTypeException
     */
    public function save()
    {
        $data = $this->build();
        $type = static::getArrayType($data);

        if ($type === static::NORMAL) {
            $attach = [];
            $detach = [];
            $sync = [];

            foreach ($data as $item) {
                if ($this->options & Manager::SAVE) {
                    $this->relation->save($item);
                }

                if ($this->options & Manager::ATTACH) {
                    $attach[$item->getKey()] = $this->getPivotAttributes($item);
                }

                if ($this->options & Manager::DETACH) {
                    $detach[] = $item->getKey();
                }

                if ($this->options & Manager::DELETE && $item->exists) {
                    $key = $item->getKey();
                    $item->delete();

                    if ($this->options & Manager::DETACH && !Manager::isSoftDeleting($item)) {
                        $detach[] = $key;
                    }
                }

                if ($item->exists) {
                    $sync[$item->getKey()] = $this->getPivotAttributes($item);
                }
            }

            if ($this->options & Manager::SYNC) {
                $this->relation->sync($sync);
            } else {
                if ($detach) {
                    $this->relation->detach($detach);
                }

                if ($attach) {
                    $this->relation->sync($detach, false);
                }
            }
        } elseif ($this->options & Manager::SYNC) {
            $this->relation->sync($this->data);
        }

        return true;
    }

    /**
     * Check if the relation is waiting for parent model to be saved.
     *
     * @return bool
     */
    public function isWaitingForParent()
    {
        return !$this->relation->getParent()->exists;
    }

    /**
     * Validate data depending on it's type.
     *
     * @param \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model $data
     *
     * @throws \Ethereal\Database\Relations\Exceptions\InvalidTypeException
     */
    public function validate($data)
    {
        $type = static::getArrayType($this->data);

        if ($type === static::NORMAL) {
            $this->validateType($data);
        } else {
            if ($data->has(0)) {
                throw new InvalidTypeException("`{$this->relationName}` relation in sync mode cannot contain index of 0.");
            }

            foreach ($this->data as $key => $item) {
                if (!is_numeric($key)) {
                    throw new InvalidTypeException("`{$this->relationName}` relation in sync mode key can only be numeric.");
                }

                if (!is_numeric($item) && !is_array($item)) {
                    throw new InvalidTypeException("`{$this->relationName}` relation in sync mode can only consist of int and array.");
                }
            }
        }
    }

    /**
     * Get model pivot keys.
     *
     * @param \Illuminate\Database\Eloquent\Model $item
     *
     * @return array
     */
    protected function getPivotAttributes(Model $item)
    {
        $attr = [];

        if ($item->relationLoaded('pivot')) {
            $attr = array_except($item->getRelation('pivot')->getAttributes(), [$this->getOtherKeyName()]);

            return $attr;
        }

        return $attr;
    }

    /**
     * Return foreign key column name.
     *
     * @return string
     */
    public function getOtherKeyName()
    {
        return last(explode('.', $this->relation->getOtherKey()));
    }

    /**
     * Determine if the list is a normal relations array.
     *
     * @param \Illuminate\Support\Collection|array $list
     *
     * @return bool
     */
    public static function getArrayType($list)
    {
        $isAssoc = $list instanceof Arrayable ? Arr::isAssoc($list->toArray()) : Arr::isAssoc($list);
        $allArrays = true;

        foreach ($list as $item) {
            if ($allArrays && !is_array($item)) {
                $allArrays = false;
            }

            if (is_numeric($item) || ($isAssoc && is_array($item) && !$allArrays)) {
                return static::SYNC;
            }
        }

        if ($allArrays && $isAssoc) {
            return static::SYNC;
        }

        return static::NORMAL;
    }
}
