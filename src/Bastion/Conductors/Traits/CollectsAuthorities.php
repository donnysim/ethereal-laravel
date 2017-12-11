<?php

namespace Ethereal\Bastion\Conductors\Traits;

use Ethereal\Bastion\Exceptions\InvalidAuthorityException;
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
     * @throws \Ethereal\Bastion\Exceptions\InvalidAuthorityException
     */
    protected function collectAuthorities($listOrClass, $ids = []): array
    {
        if (\is_array($listOrClass) || $listOrClass instanceof Traversable) {
            return $listOrClass;
        }

        if ($listOrClass instanceof Model) {
            if (!$listOrClass->exists) {
                throw new InvalidAuthorityException('Authority with does not exist.');
            }

            return [$listOrClass];
        }

        if (\is_string($listOrClass) && !empty($ids)) {
            $model = new $listOrClass();
            $models = $listOrClass::whereIn($model->getKeyName(), $ids)->get([$model->getKeyName()]);

            if (\count($ids) !== $models->count()) {
                $missing = array_values(array_diff($ids, $models->pluck($model->getKeyName())->all()));
                throw new InvalidAuthorityException('Authority with id ' . array_first($missing) . ' does not exist.');
            }

            return $models->all();
        }

        return [];
    }
}
