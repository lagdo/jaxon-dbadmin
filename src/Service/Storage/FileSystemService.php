<?php

namespace Lagdo\DbAdmin\Support\Service\Storage;

use Jaxon\Upload\Manager\FileNameInterface;
use Lagdo\Facades\Logger;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToReadFile;
use RuntimeException;

use function base_convert;
use function extension_loaded;
use function floor;
use function microtime;
use function pathinfo;
use function stream_filter_append;
use function strtolower;
use function str_pad;

/**
 * FileSystem utilities.
 */
class FileSystemService implements FileNameInterface
{
    /**
     * Epoch for unique ids computation.
     *
     * @var int
     */
    private const EPOCH = 1735689600000;

    /**
     * @var int
     */
    private int $lastTimestamp = -1;

    /**
     * @var int
     */
    private int $sequence = 0;

    /**
     * @var int
     */
    private int $workerId = (1 & 0x3FF) << 12;

    /**
     * @return int
     */
    private function timestamp(): int
    {
        $timestamp = (int)floor(microtime(true) * 1000);
        if ($timestamp !== $this->lastTimestamp) {
            $this->sequence = 0;
            $this->lastTimestamp = $timestamp;
            return $timestamp;
        }

        // Increment sequence up to 4095 within the same millisecond.
        $this->sequence = ($this->sequence + 1) & 0xFFF;
        if ($this->sequence === 0) {
            // Sequence overflow: will happen only if more than
            // 4096 ids are generated in the same millisecond.
            // Wait until the next millisecond to avoid collisions.
            while ($timestamp <= $this->lastTimestamp) {
                $timestamp = (int)floor(microtime(true) * 1000);
            }
        }
        $this->lastTimestamp = $timestamp;
        return $timestamp;
    }

    /**
     * @return string
     */
    private function createRandomName(): string
    {
        $timestamp = $this->timestamp();
        // 64-bit assembly: 41-bit timestamp | 10-bit worker ID | 12-bit sequence
        $id = (($timestamp - self::EPOCH) << 22) | $this->workerId | $this->sequence;

        // Native Base36 conversion (0-9a-z) + left-padding to 13 chars for lexicographical sorting
        return str_pad(base_convert((string)$id, 10, 36), 13, '0', STR_PAD_LEFT);
    }

    /**
     * Function of Jaxon\Upload\Manager\FileNameInterface.
     *
     * @param int $_
     *
     * @return string
     */
    public function random(int $_): string
    {
        return $this->createRandomName();
    }

    /**
     * @return string
     */
    public function randomName(): string
    {
        return $this->createRandomName();
    }

    /**
     * @param resource $stream
     *
     * @return void
     */
    private function attachZlibFilter($stream): void
    {
        if (!extension_loaded('zlib')) {
            throw new RuntimeException('The PHP "zlib" extension is required to read Gzip and Zlib files.');
        }

        // window = 15 + 32 (47) enables automatic detection of both Gzip and Zlib headers
        $filter = stream_filter_append($stream, 'zlib.inflate', STREAM_FILTER_READ, [
            'window' => 15 + 32,
        ]);
        if ($filter === false) {
            throw new RuntimeException('Failed to attach the zlib.inflate filter to the stream.');
        }
    }

    /**
     * @param resource $stream
     *
     * @return void
     */
    private function attachBzip2Filter($stream): void
    {
        if (!extension_loaded('bz2')) {
            throw new RuntimeException('The PHP "bz2" extension is required to read Bzip2 files.');
        }

        $filter = stream_filter_append($stream, 'bzip2.decompress', STREAM_FILTER_READ);
        if ($filter === false) {
            throw new RuntimeException('Failed to attach the bzip2.decompress filter to the stream.');
        }
    }

    /**
     * @param Filesystem $fs
     * @param string $path
     *
     * @return resource|null
     */
    public function openStream(Filesystem $fs, string $path): mixed
    {
        try {
            $fileStream = $fs->readStream($path);
        } catch (FilesystemException|UnableToReadFile $e) {
            Logger::error('Unable to open file: ' . $e->getMessage());
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        try {
            switch ($extension) {
                case 'gz':
                case 'gzip':
                    $this->attachZlibFilter($fileStream);
                    break;
                case 'bz2':
                case 'bzip2':
                    $this->attachBzip2Filter($fileStream);
                    break;
                default:
            };
        } catch (RuntimeException $e) {
            Logger::error('Unable to open compressed file: ' . $e->getMessage());
            return null;
        }

        return $fileStream;
    }
}
