<?php

namespace Lagdo\DbAdmin\Db\Exception;

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
