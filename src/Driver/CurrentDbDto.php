<?php

namespace Lagdo\DbAdmin\Support\Driver;

/**
 * The database the app is currently connected to.
 */
class CurrentDbDto
{
    /**
     * @var string
     */
    public string $server = '';

    /**
     * @var string
     */
    public string $name = '';

    /**
     * @var string
     */
    public string $schema = '';

    /**
     * @param string $server
     *
     * @return void
     */
    public function setServer(string $server): void
    {
        $this->server = $server;
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string $schema
     *
     * @return void
     */
    public function setSchema(string $schema): void
    {
        $this->schema = $schema;
    }
}
