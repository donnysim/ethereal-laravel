<?php

namespace Ethereal\Database\Relations\Handlers;

use Ethereal\Database\Relations\Manager;

class HasManyHandler extends Handler
{
    /**
     * Wrap data into model or collection of models based on relation type.
     *
     * @return \Ethereal\Database\Ethereal|\Illuminate\Database\Eloquent\Collection
     * @throws \Ethereal\Database\Relations\Exceptions\InvalidTypeException
     */
    public function build()
    {
        $model = $this->hydrateCollection($this->data);
        $this->validate();

        return $model;
    }

    /**
     * Save relation data.
     *
     * @return bool
     * @throws \Ethereal\Database\Relations\Exceptions\InvalidTypeException
     */
    public function save()
    {
        $collection = $this->build();

        $exists = 0;

        foreach ($collection as $item) {
            if ($this->options & Manager::SAVE) {
                $this->relation->save($item);
            }

            if ($this->options & Manager::DELETE && $item->exists) {
                $item->delete();
            }

            $exists += (int)$item->exists;
        }

        if ($this->options & Manager::SYNC) {
            if ($collection->isEmpty() || $exists === 0) {
                $this->relation->delete();
            } else {
                $modelKeyName = $collection->first()->getKeyName();

                foreach ($collection->pluck($modelKeyName)->chunk(20) as $chunk) {
                    $this->relation->whereNotIn($modelKeyName, $chunk->toArray())->delete();
                }
            }
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
        if ($this->options === Manager::DELETE) {
            return false;
        }

        return !$this->relation->getParent()->exists;
    }
}
