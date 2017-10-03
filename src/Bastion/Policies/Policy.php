<?php

namespace Ethereal\Bastion\Policies;

trait Policy
{
    /**
     * Allow access.
     *
     * @param string|null $reason
     *
     * @return \Ethereal\Bastion\Policies\PolicyResult
     */
    protected function allow($reason = null)
    {
        return new PolicyResult(true, $reason);
    }

    /**
     * Deny access.
     *
     * @param string|null $reason
     *
     * @return \Ethereal\Bastion\Policies\PolicyResult
     */
    protected function deny($reason = null)
    {
        return new PolicyResult(false, $reason);
    }
}
