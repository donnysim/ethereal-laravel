<?php

namespace Ethereal\Bastion\Database\Traits;

use Ethereal\Bastion\Helper;

trait Authority
{
    use HasRoles, HasAbilities;

    /**
     * Get authority permissions.
     *
     * @return \Ethereal\Bastion\Map
     * @throws \InvalidArgumentException
     */
    public function permissions()
    {
        return Helper::bastion()->permissions($this);
    }

    /**
     * Check authority for permissions.
     *
     * @param string|null $group
     * @param \Illuminate\Database\Eloquent\Model|null $parent
     *
     * @return \Ethereal\Bastion\Conductors\CheckProxy
     * @throws \InvalidArgumentException
     */
    public function check($group = null, $parent = null)
    {
        return Helper::bastion()->check($this)->group($group)->parent($parent);
    }

    /**
     * Manage authority roles and permissions.
     *
     * @param string|null $group
     * @param \Illuminate\Database\Eloquent\Model|null $parent
     *
     * @return \Ethereal\Bastion\Conductors\ManageProxy
     */
    public function manage($group = null, $parent = null)
    {
        return Helper::bastion()->manage($this)->group($group)->parent($parent);
    }
}
