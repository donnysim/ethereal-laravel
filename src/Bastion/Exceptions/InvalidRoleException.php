<?php

namespace Ethereal\Bastion\Exceptions;

use Exception;

class InvalidRoleException extends Exception
{
    /**
     * MissingRoleException constructor.
     *
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }
}
