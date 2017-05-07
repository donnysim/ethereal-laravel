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
     * @param \Illuminate\Database\Eloquent\Model|null $parent
     *
     * @return \Ethereal\Bastion\Conductors\CheckProxy
     * @throws \InvalidArgumentException
     */
    public function check($parent = null)
    {
        return Helper::bastion()->check($this)->parent($parent);
    }

    /**
     * Manage authority roles and permissions.
     *
     * @param \Illuminate\Database\Eloquent\Model|null $parent
     *
     * @return \Ethereal\Bastion\Conductors\ManageProxy
     * @throws \InvalidArgumentException
     */
    public function manage($parent = null)
    {
        return Helper::bastion()->manage($this)->parent($parent);
    }
}
