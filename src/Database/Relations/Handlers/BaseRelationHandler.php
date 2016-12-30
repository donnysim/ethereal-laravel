<?php

namespace Ethereal\Database\Relations\Handlers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class BaseRelationHandler
{
    /**
     * Target relation.
     *
     * @var \Illuminate\Database\Eloquent\Relations\Relation
     */
    protected $relation;

    /**
     * Target relation name on parent.
     *
     * @var string
     */
    protected $relationName;

    /**
     * Parent model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $parent;

    /**
     * The model or model collection to be parsed.
     *
     * @var \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|array
     */
    protected $data;

    /**
     * BaseRelationHandler constructor.
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
     * @param string $relationName
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param array|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model $data
     */
    public function __construct(Relation $relation, $relationName, Model $parent, $data)
    {
        $this->relation = $relation;
        $this->relationName = $relationName;
        $this->parent = $parent;
        $this->data = $data;
    }
}
