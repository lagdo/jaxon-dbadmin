<?php

namespace Lagdo\DbAdmin\Support\Exception;

use Exception;

class DriverException extends Exception
{
    /**
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
