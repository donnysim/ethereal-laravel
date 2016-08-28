<?php

namespace Ethereal\Database\Relations\Handlers;

use Ethereal\Database\Ethereal;
use Ethereal\Database\Relations\RelationManager;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class BelongsToManyHandler extends BaseRelationHandler
{
    const NORMAL = 1;
    const SYNC = 2;

    protected $type;

    /**
     * @var \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected $relation;

    /**
     * Wrap data into model or collection of models based on relation type.
     *
     * @return Model|EloquentCollection
     * @throws \InvalidArgumentException
     */
    public function build()
    {
        $type = static::getArrayType($this->data);

        if ($type === static::NORMAL) {
            $this->boxCollection();
        } elseif ($type === static::SYNC) {
            $this->data = new EloquentCollection($this->data);
        }

        $this->validate();

        return $this->data;
    }

    /**
     * Save relation data.
     *
     * @return bool
     * @throws \Exception
     */
    public function save()
    {
        $type = static::getArrayType($this->data);

        if ($type === static::NORMAL) {
            foreach ($this->data as $item) {
                /** @var Model $item */

                if ($this->relationOptions & Ethereal::OPTION_SAVE) {
                    $this->relation->save($item);
                }

                if ($this->relationOptions & Ethereal::OPTION_ATTACH) {
                    $this->relation->sync([$item->getKey() => $this->getPivotAttributes($item)], false);
                }

                if ($this->relationOptions & Ethereal::OPTION_DETACH) {
                    $this->relation->detach($item);
                }

                if ($this->relationOptions & Ethereal::OPTION_DELETE) {
                    if ($item->exists && ! $item->delete()) {
                        return false;
                    }

                    if (! $this->relationOptions & Ethereal::OPTION_DETACH && ! RelationManager::isSoftDeleting($item)) {
                        $this->relation->detach($item);
                    }

                    if ($this->shouldRemoveAfterDelete()) {
                        $this->removeCollectionRelation($item);
                    }
                }
            }

            if ($this->relationOptions & Ethereal::OPTION_SYNC) {
                $sync = [];

                foreach ($this->data as $item) {
                    /** @var Model $item */

                    $sync[$item->getKey()] = $this->getPivotAttributes($item);
                }

                $this->relation->sync($sync);
            }
        } elseif ($type === static::SYNC) {
            if ($this->relationOptions & Ethereal::OPTION_SYNC) {
                $this->relation->sync($this->data);
            }
        }

        return true;
    }

    /**
     * Validate relation data.
     *
     * @throws \InvalidArgumentException
     */
    public function validate()
    {
        $type = static::getArrayType($this->data);

        if ($type === static::NORMAL) {
            $this->validateClass($this->data);
        } elseif ($type === static::SYNC) {

            if ($this->data->has(0)) {
                throw new InvalidArgumentException("`{$this->relationName}` relation in sync mode cannot contain index of 0.");
            }

            foreach ($this->data as $key => $item) {
                if (! is_numeric($key)) {
                    throw new InvalidArgumentException("`{$this->relationName}` relation in sync mode key can only be numeric.");
                }

                if (! is_numeric($item) && ! is_array($item)) {
                    throw new InvalidArgumentException("`{$this->relationName}` relation in sync mode can only consist of int and array.");
                }
            }
        }
    }

    /**
     * Check if the relation is waiting for parent model to be saved.
     *
     * @return bool
     */
    public function isWaitingForParent()
    {
        return ! $this->parent->exists;
    }

    /**
     * Check if the list is a normal relations array.
     *
     * @param \Illuminate\Support\Collection|array $list
     * @return bool
     */
    public static function getArrayType($list)
    {
        if ($list instanceof Arrayable) {
            $isAssoc = Arr::isAssoc($list->toArray());
        } else {
            $isAssoc = Arr::isAssoc($list);
        }

        $allArrays = true;

        foreach ($list as $item) {
            if ($allArrays && ! is_array($item)) {
                $allArrays = false;
            }

            if (is_numeric($item) || ($isAssoc && is_array($item) && ! $allArrays)) {
                return static::SYNC;
            }
        }

        if ($allArrays && $isAssoc) {
            return static::SYNC;
        }

        return static::NORMAL;
    }

    /**
     * Return foreign key column name.
     *
     * @return string
     */
    public function getOtherKeyName()
    {
        return explode('.', $this->relation->getOtherKey())[1];
    }

    /**
     * Get model pivot keys.
     *
     * @param \Illuminate\Database\Eloquent\Model $item
     * @return array
     */
    protected function getPivotAttributes(Model $item)
    {
        $attrs = [];

        if ($item->relationLoaded('pivot')) {
            $attrs = array_except($item->getRelation('pivot')->getAttributes(), [$this->getOtherKeyName()]);

            return $attrs;
        }

        return $attrs;
    }
}