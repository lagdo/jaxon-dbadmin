<?php

namespace Lagdo\DbAdmin\Tests;

use Lagdo\DbAdmin\Package;
use PHPUnit\Framework\TestCase;
use Exception;

/**
 * @covers Jaxon\Request
 */
final class ServerTest extends TestCase
{
    /**
     * The Jaxon DbAdmin package
     *
     * @var Package
     */
    protected $package;

    /**
     * The facade to database functions
     *
     * @var DbAdmin
     */
    // protected $dbAdmin;

    // public static function setUpBeforeClass()
    // {
    // }

    /**
     * @expectedException Exception
     */
    public function testException()
    {
        throw new Exception('');
    }

    public function testFunction()
    {
    }
}
