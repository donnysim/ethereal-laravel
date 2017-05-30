<?php

namespace Ethereal\Bastion\Conductors\Traits;

use Traversable;

trait CollectsAuthorities
{
    /**
     * Collect authority list.
     *
     * @param \Illuminate\Database\Eloquent\Model|string|array $listOrClass
     * @param mixed $ids
     *
     * @return array
     */
    protected function collectAuthorities($listOrClass, $ids)
    {
        if (is_string($listOrClass)) {
            $authorities = [];

            foreach ((array)$ids as $id) {
                /** @var \Ethereal\Database\Ethereal $model */
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
