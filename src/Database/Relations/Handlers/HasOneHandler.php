<?php

namespace Ethereal\Database\Relations\Handlers;

use Ethereal\Database\Ethereal;

class HasOneHandler extends BaseRelationHandler
{
    /**
     * @var \Illuminate\Database\Eloquent\Relations\HasOne
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
        $this->boxModel()->validate();

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
        if ($this->relationOptions & Ethereal::OPTION_SAVE) {
            $this->relation->save($this->data);
        }

        if ($this->relationOptions & Ethereal::OPTION_DELETE) {
            if ($this->data->exists && !$this->data->delete()) {
                return false;
            }

            if ($this->shouldRemoveAfterDelete()) {
                $this->removeModelRelation();
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
        if ($this->relationOptions === Ethereal::OPTION_DELETE) {
            return false;
        }

        return !$this->parent->exists;
    }
}
