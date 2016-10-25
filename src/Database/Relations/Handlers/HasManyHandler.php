<?php

namespace Ethereal\Database\Relations\Handlers;

use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Model;

class HasManyHandler extends BaseRelationHandler
{
    /**
     * @var \Illuminate\Database\Eloquent\Relations\HasMany
     */
    protected $relation;

    /**
     * Wrap data into model or collection of models based on relation type.
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     * @throws \InvalidArgumentException
     */
    public function build()
    {
        $this->boxCollection()->validate();

        return $this->data;
    }

    /**
     * Validate relation data.
     *
     * @throws \InvalidArgumentException
     */
    public function validate()
    {
        $this->validateClass($this->data);
    }

    /**
     * Save relation data.
     *
     * @return bool
     * @throws \Exception
     */
    public function save()
    {
        foreach ($this->data as $item) {
            if (!$this->process($item)) {
                return false;
            }
        }

        if ($this->relationOptions & Ethereal::OPTION_SYNC) {
            return $this->sync();
        }

        return true;
    }

    /**
     * Process single item.
     *
     * @param \Illuminate\Database\Eloquent\Model $item
     *
     * @return bool
     * @throws \Exception
     */
    public function process(Model $item)
    {
        if ($this->relationOptions & Ethereal::OPTION_SAVE) {
            $this->relation->save($item);
        }

        if ($this->relationOptions & Ethereal::OPTION_DELETE) {
            if ($item->exists && !$item->delete()) {
                return false;
            }

            if ($this->shouldRemoveAfterDelete()) {
                $this->removeCollectionRelation($item);
            }
        }

        return true;
    }

    /**
     * Sync provided model list so that the relation only contains
     * the provided entries. Works in a similar way as BelongsToMany
     * sync method does.
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function sync()
    {
        $this->validate();

        /** @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query */
        $result = $this->fireModelEvent('syncing', [$this->relationName, $this->relation, $this->data]);

        if ($result === false) {
            return false;
        }

        if ($this->data->isEmpty()) {
            $query->delete();
        } else {
            $modelKeyName = $this->data->first()->getKeyName();
            $keys = $this->data->pluck($modelKeyName)->toArray();
            $this->relation->whereNotIn($modelKeyName, $keys)->delete();
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
        if ($this->relationOptions === Ethereal::OPTION_DELETE) {
            return false;
        }

        return !$this->parent->exists;
    }
}
