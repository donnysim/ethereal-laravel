<?php

namespace Ethereal\Bastion\Policies;

class PolicyResult
{
    /**
     * Authorization passed.
     *
     * @var bool
     */
    protected $passed = false;

    /**
     * Reason for denial if provided.
     *
     * @var null|string
     */
    protected $reason;

    /**
     * Details constructor.
     *
     * @param bool $passed
     * @param null|string $reason
     */
    public function __construct($passed, $reason = null)
    {
        $this->passed = $passed;
        $this->reason = $reason;
    }

    /**
     * Determine if access has been granted.
     *
     * @param mixed $result
     *
     * @return bool
     */
    public static function accessDenied($result): bool
    {
        return $result === false || ($result instanceof self && $result->denied());
    }

    /**
     * Determine if access has been granted.
     *
     * @param mixed $result
     *
     * @return bool
     */
    public static function accessGranted($result): bool
    {
        return $result === true || ($result instanceof self && $result->allowed());
    }

    /**
     * Allow access.
     *
     * @param mixed $reason
     *
     * @return \Ethereal\Bastion\Policies\PolicyResult
     */
    public static function allow($reason = null): PolicyResult
    {
        return new static(true, $reason);
    }

    /**
     * Deny access.
     *
     * @param mixed $reason
     *
     * @return \Ethereal\Bastion\Policies\PolicyResult
     */
    public static function deny($reason = null): PolicyResult
    {
        return new static(false, $reason);
    }

    /**
     * Create authorization status from result.
     *
     * @param mixed $result
     * @param string|null $reason Reason if none provided.
     *
     * @return \Ethereal\Bastion\Policies\PolicyResult
     */
    public static function fromResult($result, $reason = null): PolicyResult
    {
        if ($result instanceof self) {
            return $result;
        }

        return new static(static::accessGranted($result), $reason);
    }

    /**
     * Determine if the check passed.
     *
     * @return bool
     */
    public function allowed(): bool
    {
        return $this->passed;
    }

    /**
     * Determine if the check failed.
     *
     * @return bool
     */
    public function denied(): bool
    {
        return !$this->passed;
    }

    /**
     * Reason why the action was allowed or denied.
     *
     * @return null|string
     */
    public function reason()
    {
        return $this->reason;
    }
}
