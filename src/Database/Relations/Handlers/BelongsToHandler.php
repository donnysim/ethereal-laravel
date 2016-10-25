<?php

namespace Ethereal\Database\Relations\Handlers;

use Ethereal\Database\Ethereal;
use Ethereal\Database\Relations\RelationManager;

class BelongsToHandler extends BaseRelationHandler
{
    /**
     * @var \Illuminate\Database\Eloquent\Relations\BelongsTo
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
            $this->data->save();

            if ($this->data->exists) {
                $this->parent[$this->relation->getForeignKey()] = $this->data->getKey();
            } else {
                return false;
            }
        }

        if ($this->relationOptions & Ethereal::OPTION_DELETE) {
            if ($this->data->exists && !$this->data->delete()) {
                return false;
            }

            if ($this->shouldRemoveAfterDelete()) {
                $this->removeModelRelation();
            }

            if (!RelationManager::isSoftDeleting($this->data)) {
                $this->parent[$this->relation->getForeignKey()] = null;
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
        return false;
    }
}
