<?php

namespace Ethereal\Database\Relations\Handlers;

use Ethereal\Database\Relations\Manager;

class HasOneHandler extends Handler
{
    /**
     * Wrap data into model or collection of models based on relation type.
     *
     * @return \Ethereal\Database\Ethereal|\Illuminate\Database\Eloquent\Collection
     * @throws \Ethereal\Database\Relations\Exceptions\InvalidTypeException
     */
    public function build()
    {
        $model = $this->hydrateModel($this->data);
        $this->validateType($model);

        return $model;
    }

    /**
     * Save relation data.
     *
     * @return bool
     * @throws \Ethereal\Database\Relations\Exceptions\InvalidTypeException
     * @throws \Exception
     */
    public function save()
    {
        $model = $this->build();

        if ($this->options & Manager::SAVE) {
            if (!$this->relation->save($model)) {
                return false;
            }
        }

        if ($this->options & Manager::DELETE && $model->exists) {
            if (!$model->delete()) {
                return false;
            }

            $model->setAttribute($this->relation->getForeignKeyName(), null);
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
