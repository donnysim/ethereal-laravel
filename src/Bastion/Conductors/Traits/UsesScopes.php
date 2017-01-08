<?php

namespace Ethereal\Bastion\Conductors\Traits;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Traversable;

trait UsesScopes
{
    /**
     * State if any entity was targeted.
     *
     * @var bool
     */
    protected $targeted = false;

    /**
     * Targeted entities.
     *
     * @var array
     */
    protected $scopeTargets = [];

    /**
     * Permission parent scope.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $scopeParent;

    /**
     * Permission group.
     *
     * @var string
     */
    protected $scopeGroup;

    /**
     * Target everything.
     *
     * @return $this
     */
    public function targetEverything()
    {
        $this->targeted = true;
        $this->scopeTargets = ['*'];

        return $this;
    }

    /**
     * Set targeted entities.
     *
     * @param \Illuminate\Database\Eloquent\Model|string|array|null $listOrClass
     * @param array|int|string|null $ids
     *
     * @return $this
     */
    public function target($listOrClass, $ids = null)
    {
        $this->targeted = $listOrClass !== null;

        if (is_array($listOrClass) || $listOrClass instanceof Traversable) {
            $this->scopeTargets = $listOrClass;
        } elseif ($listOrClass instanceof Model) {
            $this->scopeTargets = [$listOrClass];
        } elseif (!empty($ids)) {
            $models = [];

            foreach ((array)$ids as $id) {
                $model = new $listOrClass;
                $model->setAttribute($model->getKeyName(), $id);
                $model->exists = true;
                $models[] = $model;
            }
            $this->scopeTargets = $models;
        }

        return $this;
    }

    /**
     * Set parent scope for permission.
     *
     * @param \Illuminate\Database\Eloquent\Model|string $model
     * @param string|int|null $id
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function parent($model, $id = null)
    {
        if (is_string($model)) {
            if ($id === null) {
                throw new InvalidArgumentException('A valid ID is required.');
            }

            $model = new $model;
            $model->setAttribute($model->getKeyName(), $id);
        }

        $this->scopeParent = $model;

        return $this;
    }

    /**
     * Set group scope.
     *
     * @param string $group
     *
     * @return $this
     */
    public function group($group)
    {
        $this->scopeGroup = $group;

        return $this;
    }
}
