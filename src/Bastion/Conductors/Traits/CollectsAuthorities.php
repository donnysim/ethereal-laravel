<?php

namespace Ethereal\Bastion\Conductors\Traits;

use Traversable;

trait CollectsAuthorities
{
    /**
     * Collect authority list.
     *
     * @param \Illuminate\Database\Eloquent\Model|string|array $listOrClass
     * @param array $ids
     *
     * @return array
     */
    protected function collectAuthorities($listOrClass, array $ids)
    {
        if (is_string($listOrClass)) {
            $authorities = [];

            foreach ($ids as $id) {
                $model = new $listOrClass;
                $model->setAttribute($model->getKeyName(), $id);
                $model->exists = true;
                $authorities[] = $model;
            }

            return $authorities;
        } elseif (!is_array($listOrClass) && !$listOrClass instanceof Traversable) {
            return [$listOrClass];
        }

        return $listOrClass;
    }
}
