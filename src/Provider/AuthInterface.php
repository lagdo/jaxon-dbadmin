<?php

namespace Lagdo\DbAdmin\Support\Provider;

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
    public function userId(): string;

    /**
     * Get the authenticated user role
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get the authenticated user namee
     *
     * @return array<string>
     */
    public function roles(): array;

    /**
     * Link to the audit page
     *
     * @return string
     */
    public function audit(): string;

    /**
     * Link to logout the authenticated user
     *
     * @return string
     */
    public function logout(): string;
}
