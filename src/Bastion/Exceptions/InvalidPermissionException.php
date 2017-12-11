<?php

namespace Ethereal\Bastion\Exceptions;

use Exception;

class InvalidPermissionException extends Exception
{
    /**
     * InvalidPermissionException constructor.
     *
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }
}
