<?php

namespace Lagdo\DbAdmin\Support\Service\Import;

class ImportOptions
{
    public function __construct(
        public readonly array $file,
        public readonly string $extension,
        public readonly string $jsonFilename,
        public readonly string $contentType,
        public readonly string $contentDisposition,
        // The database to connect to.
        public readonly string $server,
        public readonly string $database,
        public readonly string $schema,
        // The file to import
        public readonly array $inputs,
        // The other options
        public readonly string $status,
        public readonly int $timeLimit,
        public readonly int $batchSize,
        public readonly int $queriesThreshold,
        public readonly int $durationThreshold
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
        $filename = $this->file['log'];
        return "{$this->contentDisposition}; filename=\"{$filename}\"";
    }
}
