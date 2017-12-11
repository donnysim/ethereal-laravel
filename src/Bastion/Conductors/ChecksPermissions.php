<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Model;

class ChecksPermissions
{
    /**
     * The authority against which to check for roles.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $authority;

    /**
     * Permission store.
     *
     * @var \Ethereal\Bastion\Store
     */
    protected $store;

    /**
     * ChecksPermissions constructor.
     *
     * @param \Ethereal\Bastion\Store $store
     * @param \Illuminate\Database\Eloquent\Model $authority
     */
    public function __construct($store, Model $authority)
    {
        $this->store = $store;
        $this->authority = $authority;
    }

    /**
     * Determine if authority has permission.
     *
     * @param string $action
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function can($action, $model = null): bool
    {
        $map = $this->store->getMap($this->authority);
        $permitted = $map->permissions()->filter(function ($permission) {
            return !$permission->forbid;
        })->pluck('identifier');
        $forbidden = $map->permissions()->filter(function ($permission) {
            return $permission->forbid;
        })->pluck('identifier');
        $matches = $this->buildMatches($action, $model);

        $allowed = false;
        foreach ($matches as $match) {
            if ($forbidden->contains($match)) {
                return false;
            }

            if (!$allowed && $permitted->contains($match)) {
                $allowed = true;
            }
        }

        return $allowed;
    }

    /**
     * Compile permission matches.
     *
     * @param string $action
     * @param string|\Illuminate\Database\Eloquent\Model|null $model
     *
     * @return array
     */
    protected function buildMatches($action, $model = null): array
    {
        if ($model === null) {
            $matches = ['*', '*-*', $action];
        } else {
            if (\is_string($model)) {
                $matches = ['*-*', "$action-*", "$action-" . Helper::getMorphOfClass($model), '*-' . Helper::getMorphOfClass($model)];
            } else {
                $matches = [
                    '*-*',
                    "$action-*",
                    '*-' . $model->getMorphClass(),
                    "$action-" . $model->getMorphClass(),
                    '*-' . $model->getMorphClass() . '-' . $model->getKey(),
                    "$action-" . $model->getMorphClass() . '-' . $model->getKey(),
                ];
            }
        }

        return $matches;
    }
}
