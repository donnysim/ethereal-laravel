<?php

namespace Ethereal\Bastion\Exceptions;

use Exception;

class InvalidAuthorityException extends Exception
{
    /**
     * InvalidAuthorityException constructor.
     *
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }
}
