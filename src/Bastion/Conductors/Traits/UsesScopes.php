<?php

namespace Ethereal\Bastion\Conductors\Traits;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Traversable;

trait UsesScopes
{
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

    /**
     * Set targeted entities.
     *
     * @param \Illuminate\Database\Eloquent\Model|string|array|null $listOrClass
     * @param array|int|string|null $ids
     *
     * @return array|\Traversable
     */
    protected function getTargets($listOrClass, $ids = null)
    {
        $targets = [];

        if (is_array($listOrClass) || $listOrClass instanceof Traversable) {
            $targets = $listOrClass;
        } elseif ($listOrClass instanceof Model) {
            $targets = [$listOrClass];
        } elseif (!empty($ids)) {
            $models = [];

            foreach ((array)$ids as $id) {
                $model = new $listOrClass;
                $model->setAttribute($model->getKeyName(), $id);
                $model->exists = true;
                $models[] = $model;
            }

            $targets = $models;
        } else {
            $targets[] = $listOrClass;
        }

        return $targets;
    }
}
