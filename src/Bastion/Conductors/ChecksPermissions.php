<?php

namespace Ethereal\Bastion\Conductors;

use Ethereal\Bastion\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
     * ChecksRoles constructor.
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
    public function can($action, $model = null)
    {
        $permissions = $this->store->getMap($this->authority)->getPermissionIdentifiers();
        $matches = $this->buildMatches($action, $model);

        foreach ($permissions as $permission) {
            if (\in_array($permission, $matches, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compile permission matches.
     *
     * @param string $action
     * @param string|\Illuminate\Database\Eloquent\Model|null $model
     *
     * @return array
     */
    protected function buildMatches($action, $model = null)
    {
        $matches = [];
        $parts = [\strtolower($action)];

        if ($model) {
            if (\is_string($model)) {
                $parts[] = Str::slug(\strtolower(Helper::getMorphOfClass($model)));
                $matches[] = \implode('-', $parts);
            } else {
                $parts[] = Str::slug(\strtolower($model->getMorphClass()));
                $matches[] = \implode('-', $parts);

                if ($model->exists) {
                    $parts[] = $model->getKey();
                }
            }

            $matches[] = '*-*';
        } else {
            $matches[] = '*';
        }

        $matches[] = \implode('-', $parts);

        return $matches;
    }
}
