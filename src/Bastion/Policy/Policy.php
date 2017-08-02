<?php

namespace Ethereal\Bastion\Policy;

trait Policy
{
    /**
     * Allow access.
     *
     * @param string|null $reason
     *
     * @return \Ethereal\Bastion\Policy\PolicyResult
     */
    protected function allow($reason = null)
    {
        return new PolicyResult(false, $reason);
    }

    /**
     * Deny access.
     *
     * @param string|null $reason
     *
     * @return \Ethereal\Bastion\Policy\PolicyResult
     */
    protected function deny($reason = null)
    {
        return new PolicyResult(false, $reason);
    }
}
