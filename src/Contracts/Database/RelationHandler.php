<?php

namespace Ethereal\Contracts\Database;

interface RelationHandler
{
    /**
     * Wrap data into model or collection of models based on relation type.
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     */
    public function build();

    /**
     * Save relation data.
     *
     * @return bool
     */
    public function save();

    /**
     * Validate relation data.
     *
     * @throws \InvalidArgumentException
     */
    public function validate();

    /**
     * Check if the relation is waiting for parent model to be saved.
     *
     * @return bool
     */
    public function isWaitingForParent();
}
