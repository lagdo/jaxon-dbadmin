<?php

namespace Lagdo\DbAdmin\Support\Service\Import;

use League\Flysystem\Filesystem;

class ImportOptions
{
    public function __construct(
        public readonly string $filename,
        public readonly string $extension,
        public readonly string $jsonFilename,
        public readonly string $logFilename,
        public readonly string $contentType,
        public readonly string $contentDisposition,
        // The database to connect to.
        public readonly string $server,
        public readonly string $database,
        public readonly string $schema,
        // The file to import
        public readonly Filesystem $storage,
        public readonly array $inputs,
        // The other options
        public readonly string $status,
        public readonly int $timeLimit
    )
    {}

    /**
     * @return string
     */
    public function contentType(): string
    {
        return "{$this->contentType}; charset=utf-8";
    }

    /**
     * @return string
     */
    public function contentDisposition(): string
    {
        return "{$this->contentDisposition}; filename=\"{$this->logFilename}\"";
    }
}
