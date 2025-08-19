<?php

namespace Lagdo\DbAdmin\Config;

/**
 * Get info about the authenticated user
 */
interface AuthInterface
{
    /**
     * Get the authenticated user name
     * 
     * @return string
     */
    public function user(): string;

    /**
     * Get the authenticated user role
     * 
     * @return string
     */
    public function role(): string;
}
