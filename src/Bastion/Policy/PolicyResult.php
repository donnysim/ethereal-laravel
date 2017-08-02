<?php

namespace Ethereal\Bastion\Policy;

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
    public static function accessDenied($result)
    {
        return $result === false || ($result instanceof PolicyResult && $result->denied());
    }

    /**
     * Determine if access has been granted.
     *
     * @param mixed $result
     *
     * @return bool
     */
    public static function accessGranted($result)
    {
        return $result === true || ($result instanceof PolicyResult && $result->allowed());
    }

    /**
     * Create authorization status from result.
     *
     * @param mixed $result
     * @param string|null $reason Reason if none provided.
     *
     * @return static
     */
    public static function fromResult($result, $reason = null)
    {
        if ($result instanceof PolicyResult) {
            return $result;
        }

        return new static(static::accessGranted($result), $reason);
    }

    /**
     * @return bool
     */
    public function allowed()
    {
        return $this->passed;
    }

    /**
     * @return bool
     */
    public function denied()
    {
        return !$this->passed;
    }

    /**
     * Action reason.
     *
     * @return null|string
     */
    public function reason()
    {
        return $this->reason;
    }
}
