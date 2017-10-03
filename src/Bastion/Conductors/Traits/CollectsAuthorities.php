<?php

namespace Ethereal\Bastion\Conductors\Traits;

use Illuminate\Database\Eloquent\Model;
use Traversable;

trait CollectsAuthorities
{
    /**
     * Collect authority list.
     *
     * @param \Illuminate\Database\Eloquent\Model|string|array $listOrClass
     * @param array|int|null $ids
     *
     * @return array
     */
    protected function collectAuthorities($listOrClass, $ids = [])
    {
        $targets = [];

        if (\is_array($listOrClass) || $listOrClass instanceof Traversable) {
            $targets = $listOrClass;
        } elseif ($listOrClass instanceof Model) {
            $targets = [$listOrClass];
        } elseif (!empty($ids)) {
            $models = [];

            foreach ((array)$ids as $id) {
                /** @var \Ethereal\Database\Ethereal $model */
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
