<?php

namespace Lagdo\DbAdmin\Support\Service\Export;

class ExportOptions
{
    public function __construct(
        public readonly string $filename,
        public readonly string $extension,
        public readonly string $jsonFilename,
        public readonly string $contentType,
        public readonly string $contentDisposition,
        // The database to connect to.
        public readonly string $server,
        public readonly string $schema,
        public readonly string $database,
        // The data to export
        public readonly array $databases,
        public readonly array $options,
        // The other options
        public readonly int $bufferLimit,
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
        return "{$this->contentDisposition}; filename=\"{$this->filename}\"";
    }
}
