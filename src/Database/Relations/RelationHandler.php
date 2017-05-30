<?php

namespace Ethereal\Database\Relations;

interface RelationHandler
{
    /**
     * Wrap data into model or collection of models based on relation type.
     *
     * @return \Ethereal\Database\Ethereal|\Illuminate\Database\Eloquent\Collection
     */
    public function build();

    /**
     * Save relation data.
     *
     * @return bool
     */
    public function save();

    /**
     * Check if the relation is waiting for parent model to be saved.
     *
     * @return bool
     */
    public function isWaitingForParent();
}
