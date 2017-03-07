<?php

namespace Ethereal\Bastion\Conductors;

use Illuminate\Database\Eloquent\Model;

class ManageProxy
{
    use Traits\UsesScopes, Traits\ManagesAuthority;

    /**
     * Permission store.
     *
     * @var \Ethereal\Bastion\Store
     */
    protected $store;

    /**
     * The authority against which to check for roles.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $authority;

    /**
     * ManageProxy constructor.
     *
     * @param \Ethereal\Bastion\Store $store
     * @param \Illuminate\Database\Eloquent\Model $authority
     */
    public function __construct($store, Model $authority)
    {
        $this->store = $store;
        $this->authority = $authority;
    }
}
