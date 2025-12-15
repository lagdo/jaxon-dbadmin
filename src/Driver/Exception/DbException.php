<?php

namespace Lagdo\DbAdmin\Db\Driver\Exception;

use Exception;

class DbException extends Exception
{
    /**
     * The constructor
     *
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
